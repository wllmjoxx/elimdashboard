<?php

namespace Tests\Modules\Members;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Members\Enums\Gender;
use App\Modules\Members\Enums\MembershipStatus;
use App\Modules\Members\Models\MemberProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private Church $other;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        app(SyncRolesAndPermissions::class)->handle();

        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->other = Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);
    }

    public function test_members_see_their_own_profile_with_defaults(): void
    {
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($member)
            ->get(route('members.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('members/profile')
                ->where('profile.email', $member->email)
                ->where('profile.church', 'GGP Elim')
                ->where('profile.membershipStatus', 'sympathizer')
                ->where('profile.privacy.show_phone', false)
                ->has('options.genders', 2));
    }

    public function test_members_update_personal_data_and_the_address_is_encrypted(): void
    {
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($member)
            ->post(route('members.profile.update'), $this->payload([
                'name' => 'Maria S.',
                'phone' => '0812 0000 1111',
                'address' => 'Jl. Citanduy 1, Ciputat',
                'show_birthday' => true,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $member->refresh();
        $profile = MemberProfile::query()->findOrFail($member->id);

        $this->assertSame('Maria S.', $member->name);
        $this->assertSame('0812 0000 1111', $member->getAttribute('phone'));
        $this->assertSame('Ucok', $profile->nickname);
        $this->assertSame(Gender::Female, $profile->gender);
        $this->assertSame('1990-05-17', $profile->birth_date->toDateString());
        $this->assertSame('Jl. Citanduy 1, Ciputat', $profile->address);
        $this->assertTrue($profile->privacy()['show_birthday']);
        $this->assertFalse($profile->privacy()['show_phone']);

        $raw = DB::table('member_profiles')->where('user_id', $member->id)->value('address');
        $this->assertStringNotContainsString('Citanduy', (string) $raw);
    }

    public function test_members_cannot_change_their_own_membership_data(): void
    {
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($member)->post(route('members.profile.update'), $this->payload([
            'membership_status' => 'member',
            'baptized' => true,
        ]));

        $profile = MemberProfile::query()->findOrFail($member->id);
        $this->assertSame(MembershipStatus::Sympathizer, $profile->membership_status);
        $this->assertFalse($profile->baptized);

        $this->actingAs($member)
            ->post(route('members.membership', $member->id), ['membership_status' => 'member'])
            ->assertForbidden();
    }

    public function test_photo_is_private_and_replaced_cleanly(): void
    {
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($member)->post(route('members.profile.update'), $this->payload(['photo' => self::png(400, 400)]))
            ->assertSessionHasNoErrors();
        $first = MemberProfile::query()->findOrFail($member->id)->photo;
        Storage::disk('local')->assertExists($first);

        $this->actingAs($member)->get(route('members.photo', $member->id))->assertOk();
        $this->actingAs($this->user(Role::Member, $this->elim))->get(route('members.photo', $member->id))->assertForbidden();

        $this->actingAs($member)->post(route('members.profile.update'), $this->payload(['photo' => self::png(800, 800)]));
        $second = MemberProfile::query()->findOrFail($member->id)->photo;
        Storage::disk('local')->assertMissing($first);

        $this->actingAs($member)->post(route('members.profile.update'), $this->payload(['remove_photo' => true]));
        Storage::disk('local')->assertMissing($second);
        $this->assertNull(MemberProfile::query()->findOrFail($member->id)->photo);
    }

    public function test_photos_outside_the_rules_are_refused(): void
    {
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($member)
            ->post(route('members.profile.update'), $this->payload(['photo' => self::png(100, 100)]))
            ->assertSessionHasErrors('photo');

        $this->actingAs($member)
            ->post(route('members.profile.update'), $this->payload(['photo' => self::png(400, 400)->size(3000)]))
            ->assertSessionHasErrors('photo');
    }

    public function test_invalid_personal_data_is_refused(): void
    {
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($member)
            ->post(route('members.profile.update'), $this->payload([
                'birth_date' => now()->addDay()->toDateString(),
                'gender' => 'other',
                'phone' => 'abc',
            ]))
            ->assertSessionHasErrors(['birth_date', 'gender', 'phone']);
    }

    public function test_admin_views_and_updates_membership_in_their_branch(): void
    {
        $admin = $this->user(Role::Admin, $this->elim);
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($admin)
            ->get(route('members.show', $member->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('members/show')
                ->where('profile.id', $member->id)
                ->where('can.updateMembership', true));

        $this->actingAs($admin)
            ->post(route('members.membership', $member->id), [
                'membership_status' => 'member',
                'joined_at' => '2020-01-05',
                'baptized' => true,
            ])
            ->assertRedirect();

        $profile = MemberProfile::query()->findOrFail($member->id);
        $this->assertSame(MembershipStatus::Member, $profile->membership_status);
        $this->assertSame('2020-01-05', $profile->joined_at->toDateString());
        $this->assertTrue($profile->baptized);
    }

    public function test_profiles_of_other_branches_are_hidden_from_admin(): void
    {
        $admin = $this->user(Role::Admin, $this->elim);
        $outsider = $this->user(Role::Member, $this->other);

        $this->actingAs($admin)->get(route('members.show', $outsider->id))->assertForbidden();
        $this->actingAs($admin)->post(route('members.membership', $outsider->id), ['membership_status' => 'member'])->assertForbidden();
        $this->actingAs($admin)->get(route('members.photo', $outsider->id))->assertForbidden();
    }

    public function test_prayer_team_cannot_see_member_profiles(): void
    {
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($this->user(Role::PrayerTeam, $this->elim))
            ->get(route('members.show', $member->id))
            ->assertForbidden();
    }

    public function test_members_can_request_and_cancel_account_deletion(): void
    {
        $admin = $this->user(Role::Admin, $this->elim);
        $member = $this->user(Role::Member, $this->elim);

        $this->actingAs($member)->post(route('members.profile.deletion'))->assertRedirect();
        $this->assertNotNull(MemberProfile::query()->findOrFail($member->id)->deletion_requested_at);

        $this->actingAs($admin)
            ->get(route('members.index', ['status' => 'active']))
            ->assertInertia(function (Assert $page) use ($member) {
                $row = collect($page->toArray()['props']['members']['data'])->firstWhere('id', $member->id);
                $this->assertTrue($row['deletionRequested']);
            });

        $this->actingAs($member)->delete(route('members.profile.deletion.cancel'))->assertRedirect();
        $this->assertNull(MemberProfile::query()->findOrFail($member->id)->deletion_requested_at);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Maria Siahaan',
            'nickname' => 'Ucok',
            'phone' => '0812 3456 7890',
            'birth_date' => '1990-05-17',
            'gender' => 'female',
            'address' => 'Ciputat',
            'show_birthday' => false,
            'show_phone' => false,
            ...$overrides,
        ];
    }

    private function user(Role $role, Church $church): User
    {
        $user = User::factory()->create(['church_id' => $church->id]);
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * A PNG with the given pixel size; only the header matters to the
     * rules, so no GD extension is needed.
     */
    private static function png(int $width, int $height): File
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        $bytes = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', (string) gzcompress(''))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('photo.png', $bytes);
    }
}
