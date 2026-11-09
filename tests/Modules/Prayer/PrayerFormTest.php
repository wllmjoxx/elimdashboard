<?php

namespace Tests\Modules\Prayer;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Members\Models\MemberProfile;
use App\Modules\Prayer\Enums\PrayerStatus;
use App\Modules\Prayer\Models\PrayerRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrayerFormTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
    }

    public function test_guests_see_the_form_without_prefill(): void
    {
        $this->get('/doa')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('prayer/public/form')
                ->where('prefill', null)
                ->where('captchaSiteKey', null)
                ->has('categories', 8)
                ->where('intro.reference', 'Matius 21:22'));
    }

    public function test_guest_request_is_stored_encrypted_in_the_current_week_of_the_default_branch(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00', 'Asia/Jakarta')); // Thursday

        $this->post('/doa', $this->payload())
            ->assertRedirect(route('landing.prayer'))
            ->assertSessionHas('prayer_sent');

        $prayer = PrayerRequest::query()->withoutGlobalScopes()->firstOrFail();
        $this->assertSame('Maria Siahaan', $prayer->full_name);
        $this->assertSame('Mohon doa untuk kesembuhan ibu saya.', $prayer->body);
        $this->assertSame('2026-11-09', $prayer->week_start->toDateString());
        $this->assertSame($this->elim->id, $prayer->church_id);
        $this->assertNull($prayer->user_id);
        $this->assertSame(PrayerStatus::New, $prayer->status);

        $raw = (array) DB::table('prayer_requests')->first();
        foreach (['full_name', 'phone', 'address', 'body'] as $column) {
            $this->assertStringNotContainsString('Maria', (string) $raw[$column]);
            $this->assertStringNotContainsString('kesembuhan', (string) $raw[$column]);
        }

        Carbon::setTestNow();
    }

    public function test_sunday_night_still_counts_for_the_same_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-15 23:30', 'Asia/Jakarta')); // Sunday, WIB

        $this->post('/doa', $this->payload());

        $this->assertSame('2026-11-09', PrayerRequest::query()->withoutGlobalScopes()->firstOrFail()->week_start->toDateString());

        Carbon::setTestNow();
    }

    public function test_full_name_category_and_body_are_required(): void
    {
        $this->post('/doa', ['website' => ''])
            ->assertSessionHasErrors(['full_name', 'category', 'body']);
    }

    public function test_honeypot_blocks_bots(): void
    {
        $this->post('/doa', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, PrayerRequest::query()->withoutGlobalScopes()->count());
    }

    public function test_captcha_is_checked_when_keys_are_set(): void
    {
        config(['modules.core.captcha.site_key' => 'site', 'modules.core.captcha.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
            ->push(['success' => false])
            ->push(['success' => true])]);

        $this->get('/doa')->assertInertia(fn (Assert $page) => $page->where('captchaSiteKey', 'site'));

        $this->post('/doa', $this->payload())->assertSessionHasErrors('captcha_token');
        $this->post('/doa', $this->payload(['captcha_token' => 'bad']))->assertSessionHasErrors('captcha_token');
        $this->post('/doa', $this->payload(['captcha_token' => 'good']))->assertSessionHasNoErrors();

        $this->assertSame(1, PrayerRequest::query()->withoutGlobalScopes()->count());
    }

    public function test_guests_are_rate_limited(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->post('/doa', $this->payload())->assertSessionHasNoErrors();
        }

        $this->post('/doa', $this->payload())->assertTooManyRequests();
    }

    public function test_members_get_prefilled_data_and_their_history(): void
    {
        $member = $this->member();
        $member->forceFill(['phone' => '0812 1111 2222'])->save();
        $profile = new MemberProfile(['address' => 'Ciputat', 'birth_date' => now()->subYears(30)->subDay()]);
        $profile->user_id = $member->id;
        $profile->save();

        $this->actingAs($member)
            ->get('/doa')
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefill.full_name', $member->name)
                ->where('prefill.phone', '0812 1111 2222')
                ->where('prefill.address', 'Ciputat')
                ->where('prefill.age', 30)
                ->where('remaining', 3)
                ->where('captchaSiteKey', null));

        $this->actingAs($member)
            ->post('/doa', $this->payload())
            ->assertRedirect(route('prayer.history'));

        $this->assertSame($member->id, PrayerRequest::query()->withoutGlobalScopes()->firstOrFail()->user_id);

        $this->actingAs($member)
            ->get(route('prayer.history'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('prayer/history')
                ->has('requests.data', 1)
                ->where('requests.data.0.body', 'Mohon doa untuk kesembuhan ibu saya.')
                ->where('remaining', 2));
    }

    public function test_members_may_send_three_requests_per_week(): void
    {
        $member = $this->member();

        foreach (range(1, 3) as $attempt) {
            $this->actingAs($member)->post('/doa', $this->payload())->assertSessionHasNoErrors();
        }

        $this->actingAs($member)->post('/doa', $this->payload())->assertSessionHasErrors('body');
        $this->assertSame(3, PrayerRequest::query()->withoutGlobalScopes()->count());

        Carbon::setTestNow(now()->addWeek());
        $this->actingAs($member)->post('/doa', $this->payload())->assertSessionHasNoErrors();
        Carbon::setTestNow();
    }

    public function test_history_shows_only_your_own_requests(): void
    {
        $this->post('/doa', $this->payload(['full_name' => 'Tamu']));

        $this->actingAs($this->member())
            ->get(route('prayer.history'))
            ->assertInertia(fn (Assert $page) => $page->has('requests.data', 0));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'full_name' => 'Maria Siahaan',
            'age' => 34,
            'phone' => '0812 3456 7890',
            'address' => 'Ciputat',
            'category' => 'health',
            'body' => 'Mohon doa untuk kesembuhan ibu saya.',
            'website' => '',
            ...$overrides,
        ];
    }

    private function member(): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole(Role::Member->value);

        return $user;
    }
}
