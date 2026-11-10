<?php

namespace App\Modules\Landing\Support;

/**
 * Which landing sections Admin edits as text, how they are labelled, and
 * the validation rules for each (PRD 4.3: "semua isi menjadi data awal yang
 * dapat diubah admin"). The three image segments and the gallery placement
 * have their own editors.
 */
class SectionEditor
{
    /**
     * Admin-facing name of every section key, in default page order.
     */
    public const LABELS = [
        'hero' => 'Hero (tema dan slide)',
        'segment_1' => 'Segmen 1',
        'about' => 'Tentang GGP Elim',
        'mission' => 'Misi',
        'devotional' => 'Renungan Harian',
        'schedule' => 'Jadwal Kegiatan',
        'segment_2' => 'Segmen 2',
        'prayer' => 'Ayat dan Form Doa',
        'gallery' => 'Galeri',
        'segment_3' => 'Segmen 3',
        'testimonies' => 'Kesaksian terpilih',
        'contact' => 'Alamat dan Kontak',
    ];

    /**
     * Sections edited on the generic section page.
     */
    public const EDITABLE = [
        'hero',
        'about',
        'mission',
        'devotional',
        'schedule',
        'prayer',
        'gallery',
        'testimonies',
        'contact',
    ];

    /**
     * Where each section appears on the public site.
     */
    public const PLACES = [
        'hero' => 'Home',
        'segment_1' => 'Home',
        'about' => 'Tentang',
        'mission' => 'Tentang',
        'devotional' => 'Home (sorotan)',
        'schedule' => 'Home (sorotan) dan Jadwal',
        'segment_2' => 'Home',
        'prayer' => 'Form Doa',
        'gallery' => 'Home',
        'segment_3' => 'Home',
        'testimonies' => 'Home',
        'contact' => 'Contact',
    ];

    public static function label(string $key): string
    {
        return self::LABELS[$key] ?? $key;
    }

    /**
     * Validation rules for one section's editable content.
     *
     * @return array<string, mixed>
     */
    public static function rules(string $key): array
    {
        $link = ['nullable', 'string', 'max:255', 'regex:#^(/|https?://|\#)#'];
        $verse = [
            'verse' => ['nullable', 'string', 'max:400'],
            'reference' => ['nullable', 'required_with:verse', 'string', 'max:60'],
        ];

        return match ($key) {
            'hero' => [
                'slides' => ['required', 'array', 'min:1', 'max:5'],
                'slides.*.eyebrow' => ['nullable', 'string', 'max:40'],
                'slides.*.title' => ['required', 'string', 'max:120'],
                'slides.*.text' => ['nullable', 'string', 'max:300'],
                'slides.*.verse' => ['nullable', 'string', 'max:400'],
                'slides.*.reference' => ['nullable', 'required_with:slides.*.verse', 'string', 'max:60'],
                'primary_cta.label' => ['nullable', 'required_with:primary_cta.href', 'string', 'max:40'],
                'primary_cta.href' => [...$link, 'required_with:primary_cta.label'],
                'secondary_cta.label' => ['nullable', 'required_with:secondary_cta.href', 'string', 'max:40'],
                'secondary_cta.href' => [...$link, 'required_with:secondary_cta.label'],
            ],
            'about' => [
                'title' => ['required', 'string', 'max:120'],
                'paragraphs' => ['required', 'array', 'min:1', 'max:8'],
                'paragraphs.*' => ['required', 'string', 'max:1500'],
                'milestones' => ['nullable', 'array', 'max:8'],
                'milestones.*.year' => ['required', 'string', 'max:20'],
                'milestones.*.text' => ['required', 'string', 'max:200'],
            ],
            'mission' => [
                'title' => ['required', 'string', 'max:60'],
                'text' => ['required', 'string', 'max:300'],
                ...$verse,
            ],
            'devotional' => [
                'label' => ['required', 'string', 'max:40'],
            ],
            'schedule' => [
                'title' => ['required', 'string', 'max:120'],
                'subtitle' => ['nullable', 'string', 'max:200'],
            ],
            'prayer' => [
                'title' => ['required', 'string', 'max:120'],
                'text' => ['required', 'string', 'max:400'],
                ...$verse,
            ],
            'gallery' => [
                'title' => ['required', 'string', 'max:120'],
            ],
            'testimonies' => [
                'title' => ['required', 'string', 'max:120'],
                'text' => ['nullable', 'string', 'max:300'],
            ],
            'contact' => [
                'title' => ['required', 'string', 'max:120'],
                'address' => ['required', 'string', 'max:300'],
                'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{8,30}$/'],
                'hours' => ['nullable', 'string', 'max:120'],
                'maps_query' => ['nullable', 'string', 'max:200'],
            ],
            default => [],
        };
    }

    /**
     * Friendly names for validation messages.
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'slides' => 'slide',
            'slides.*.eyebrow' => 'label kecil',
            'slides.*.title' => 'judul slide',
            'slides.*.text' => 'teks slide',
            'slides.*.verse' => 'ayat',
            'slides.*.reference' => 'rujukan ayat',
            'primary_cta.label' => 'label tombol utama',
            'primary_cta.href' => 'tautan tombol utama',
            'secondary_cta.label' => 'label tombol kedua',
            'secondary_cta.href' => 'tautan tombol kedua',
            'title' => 'judul',
            'subtitle' => 'keterangan',
            'text' => 'teks',
            'label' => 'label',
            'paragraphs' => 'paragraf',
            'paragraphs.*' => 'paragraf',
            'milestones.*.year' => 'tahun',
            'milestones.*.text' => 'keterangan tonggak',
            'verse' => 'ayat',
            'reference' => 'rujukan ayat',
            'address' => 'alamat',
            'whatsapp' => 'nomor WhatsApp',
            'hours' => 'jam operasional',
            'maps_query' => 'pencarian Google Maps',
        ];
    }

    /**
     * Keys of a section's content that the editor writes; anything else in
     * the stored content (gallery album and layout, sample tiles) is kept.
     *
     * @return list<string>
     */
    public static function fields(string $key): array
    {
        return array_values(array_unique(array_map(
            fn (string $rule) => explode('.', $rule)[0],
            array_keys(self::rules($key)),
        )));
    }
}
