<?php

if (!defined('ABSPATH')) {
    exit;
}

class Cliff_Form
{
    public static function init(): void
    {
        add_shortcode('cliff_ajanlatkero', [self::class, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function enqueue_assets(): void
    {
        if (!is_singular()) {
            return;
        }

        global $post;
        if (!$post || !has_shortcode($post->post_content, 'cliff_ajanlatkero')) {
            return;
        }

        wp_enqueue_style('cliff-form', CLIFF_FORM_URL . 'assets/css/cliff-form.css', [], CLIFF_FORM_VERSION);
        wp_enqueue_script('cliff-form', CLIFF_FORM_URL . 'assets/js/cliff-form.js', [], CLIFF_FORM_VERSION, true);
        wp_localize_script('cliff-form', 'cliffForm', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('cliff_form_nonce'),
            'maxFileSize' => wp_max_upload_size(),
        ]);
    }

    public static function render_shortcode(): string
    {
        ob_start();
        self::render_form();
        return ob_get_clean();
    }

    /* =========================================
       Option groups + defaults
       ========================================= */

    public static function default_options(): array
    {
        return [
            'kialakitas' => [
                ['key' => 'egyenes',    'label' => 'Egyenes',                  'image' => ''],
                ['key' => 'ketoldalon', 'label' => 'Kétoldalon elhelyezkedő',  'image' => ''],
                ['key' => 'l-alaku',    'label' => 'L-alakú',                  'image' => ''],
                ['key' => 'u-alaku',    'label' => 'U-alakú',                  'image' => ''],
                ['key' => 'g-alaku',    'label' => 'G-alakú',                  'image' => ''],
            ],
            'stilus' => [
                ['key' => 'classic', 'label' => 'Classic', 'image' => ''],
                ['key' => 'country', 'label' => 'Country', 'image' => ''],
                ['key' => 'jazz',    'label' => 'Jazz',    'image' => ''],
                ['key' => 'pop',     'label' => 'Pop',     'image' => ''],
                ['key' => 'swing',   'label' => 'Swing',   'image' => ''],
                ['key' => 'electro', 'label' => 'Electro', 'image' => ''],
            ],
            'szinvilag' => [
                ['key' => 'vilagos', 'label' => 'Világos színek',  'image' => ''],
                ['key' => 'sotet',   'label' => 'Sötét színvilág', 'image' => ''],
            ],
            'konyhapult' => [
                ['key' => 'natur-ko',  'label' => 'Natúr kő',                  'image' => ''],
                ['key' => 'keramia',   'label' => 'Kerámia',                   'image' => ''],
                ['key' => 'kvarc',     'label' => 'Kvarc kompozit',            'image' => ''],
                ['key' => 'laminalt',  'label' => 'Laminált – kő és fahatású', 'image' => ''],
                ['key' => 'tomorfa',   'label' => 'Tömörfa',                   'image' => ''],
            ],
            'suto' => [
                ['key' => 'szemmagassagban', 'label' => 'Szemmagasságban', 'image' => ''],
                ['key' => 'alul',            'label' => 'Alul',            'image' => ''],
            ],
            'huto' => [
                ['key' => 'beepitett',     'label' => 'Beépített',     'image' => ''],
                ['key' => 'szabadon-allo', 'label' => 'Szabadon álló', 'image' => ''],
            ],
            'elszivo' => [
                ['key' => 'szabadon-allo', 'label' => 'Szabadon álló', 'image' => ''],
                ['key' => 'beepitett',     'label' => 'Beépített',     'image' => ''],
            ],
            'fozofelulet' => [
                ['key' => 'gaz',       'label' => 'Gáz',       'image' => ''],
                ['key' => 'keramia',   'label' => 'Kerámia',   'image' => ''],
                ['key' => 'indukcios', 'label' => 'Indukciós', 'image' => ''],
            ],
            'mosogatogep' => [
                ['key' => 'teljesen-integralt',   'label' => 'Teljesen integrált',   'image' => ''],
                ['key' => 'latszo-kezelofelulet', 'label' => 'Látszó kezelőfelület', 'image' => ''],
            ],
            'ingatlan' => [
                ['key' => 'csaladi-haz', 'label' => 'Családi ház', 'image' => ''],
                ['key' => 'lakopark',    'label' => 'Lakópark',    'image' => ''],
                ['key' => 'tarsas-haz',  'label' => 'Társas ház',  'image' => ''],
                ['key' => 'nyaralo',     'label' => 'Nyaraló',     'image' => ''],
            ],
            'idopont' => [
                ['key' => '1-2-honap',  'label' => '1-2 hónapon belül', 'image' => ''],
                ['key' => '4-5-honap',  'label' => '4-5 hónap múlva',  'image' => ''],
                ['key' => 'erdeklodok', 'label' => 'Érdeklődök',       'image' => ''],
            ],
        ];
    }

    public static function get_options(string $group): array
    {
        $all = get_option('cliff_form_options', []);
        if (!empty($all[$group])) {
            return $all[$group];
        }
        $defaults = self::default_options();
        return $defaults[$group] ?? [];
    }

    /* =========================================
       Text defaults
       ========================================= */

    public static function defaults(): array
    {
        return [
            // General
            'main_title'   => 'Online Ajánlatkérés',
            'footer_text'  => 'Copyright © ' . date('Y') . ' Cliff Konyhák | Minden jog fenntartva',

            // Nav labels
            'nav_step0' => 'E-mail cím',
            'nav_step1' => 'Konyha kialakítása',
            'nav_step2' => 'Konyha stílusa',
            'nav_step3' => 'Színvilág',
            'nav_step4' => 'Konyhapult',
            'nav_step5' => 'Konyhagépek',
            'nav_step6' => 'Adatok',
            'nav_step7' => 'Időpont, elérhetőség',
            'nav_step8' => 'Kész',

            // Step 0
            's0_title'             => 'Kérje ajánlatunkat új Cliff konyhájára, egyszerűen, gyorsan!',
            's0_desc1'             => 'Kérjük, válaszoljon a kérdésekre, e-mail címének, a konyha részleteinek és elérhetőségének megadásával.',
            's0_desc2'             => 'A form kitöltését követően a lakhelyéhez legközelebbi Cliff konyhastúdió munkatársa fogja felvenni Önnel a kapcsolatot!',
            's0_email_label'       => 'Kérjük adja meg e-mail címét:',
            's0_email_placeholder' => 'pelda@email.com',
            's0_button'            => 'Ajánlatkérés indítása',

            // Step 1
            's1_title' => 'Konyha kialakítása',
            's1_desc'  => 'Válassza ki a konyha elrendezését!',

            // Step 2
            's2_title'  => 'Konyha stílusa',
            's2_desc'   => 'Válassza ki melyik stílusirány áll legközelebb Önhöz, többet is megadhat!',
            's2_button' => 'Tovább',

            // Step 3
            's3_title' => 'Színvilág',
            's3_desc'  => 'Milyen színvilágot képzel el?',

            // Step 4
            's4_title' => 'Konyhapult',
            's4_desc'  => 'Milyen konyhapultot szeretne?',

            // Step 5
            's5_title'             => 'Konyhagépek',
            's5_desc'              => 'Válassza ki a kívánt konyhagépeket!',
            's5_button'            => 'Tovább',
            's5_suto_title'        => 'Sütő',
            's5_huto_title'        => 'Hűtő',
            's5_elszivo_title'     => 'Elszívó',
            's5_fozofelulet_title' => 'Főzőfelület',
            's5_mosogatogep_title' => 'Mosogatógép',

            // Step 6
            's6_title'             => 'Adatok',
            's6_desc'              => 'Adja meg az ingatlan és konyha részleteit!',
            's6_ingatlan_label'    => 'Ingatlan típusa:',
            's6_meret_label'       => 'Konyha méret:',
            's6_meret_placeholder' => 'méretek, egyéb megjegyzés',
            's6_alaprajz_label'    => 'Alaprajz:',
            's6_foto_label'        => 'Fotó, skicc:',
            's6_button'            => 'Tovább',

            // Step 7
            's7_title'               => 'Időpont, elérhetőség',
            's7_intro'               => 'Ahhoz hogy személyre szabott ajánlatot tudjunk készíteni Önnek, kérjük adja meg telefonszámát és irányítószámát, hogy az Önhöz legközelebb lévő Cliff konyhastúdió felvegye Önnel a kapcsolatot egy ingyenes konzultációra az ajánlatkéréshez kapcsolódóan.',
            's7_note'                => 'Telefonszám hiányában a megadott email címen keressük fel, egy személyes időpont egyeztetésre.',
            's7_telefon_label'       => 'Telefonszám:',
            's7_telefon_placeholder' => '+36 30 123 4567',
            's7_irsz_label'          => 'Irányítószám:',
            's7_irsz_placeholder'    => '1234',
            's7_idopont_label'       => 'Mikor szeretné az új konyháját:',
            's7_adatvedelem'         => 'Az <a href="{{link}}" target="_blank">Adatvédelmi tájékoztatót</a> elolvastam és elfogadom',
            's7_marketing'           => 'Hozzájárulok, hogy a Cliff konyhabútor Kft. személyre szabott ajánlatot, tájékoztatást küldjön a konyhabútorairól, újdonságairól.',
            's7_button'              => 'Küldés',

            // Step 8 - Thank you
            's8_title'         => 'Köszönjük ajánlatkérését!',
            's8_body'          => 'Köszönjük, hogy ajánlatkérésével megkeresett Bennünket! Reméljük, hamarosan egy Cliff konyha tulajdonosaként köszönthetjük!',
            's8_contact_intro' => 'Egyéb kérdésével, észrevételeivel forduljon hozzánk az alábbi elérhetőségeken:',
            's8_telefon'       => '+36 30/269-4362',
            's8_email'         => 'koordinator@cliff.hu',
            's8_cities'        => 'Sopron | Budapest | Debrecen | Győr | Szombathely | Kecskemét',

            // Image keys (img_ prefix)
            'img_s0_hero' => '',
            'img_s5_suto'        => 'https://www.cliffkonyhabutor.hu/wp-content/uploads/2026/04/cliff-s5-suto.webp',
            'img_s5_huto'        => 'https://www.cliffkonyhabutor.hu/wp-content/uploads/2026/04/cliff-s5-huto.webp',
            'img_s5_elszivo'     => 'https://www.cliffkonyhabutor.hu/wp-content/uploads/2026/04/cliff-s5-elszivo.webp',
            'img_s5_fozofelulet' => 'https://www.cliffkonyhabutor.hu/wp-content/uploads/2026/04/cliff-s5-fozofelulet.webp',
            'img_s5_mosogatogep' => 'https://www.cliffkonyhabutor.hu/wp-content/uploads/2026/04/cliff-s5-mosogatogep.webp',
            'img_s7_hero' => '',
            'img_s8_hero' => '',
        ];
    }

    /* =========================================
       Main render
       ========================================= */

    private static function render_form(): void
    {
        $nav_labels = [];
        for ($i = 0; $i <= 8; $i++) {
            $nav_labels[] = cliff_text("nav_step{$i}");
        }

        ?>
        <div id="cliff-wizard" class="cliff-wizard">
            <nav class="cliff-sidebar">
                <div class="cliff-logo">
                    <?php
                    $logo = get_option('cliff_form_logo');
                    if ($logo) {
                        echo '<img src="' . esc_url($logo) . '" alt="Cliff Konyhabútor">';
                    } else {
                        echo '<span class="cliff-logo-text">Cliff</span>';
                    }
                    ?>
                </div>
                <ul class="cliff-steps-nav">
                    <?php foreach ($nav_labels as $i => $label): ?>
                        <li class="cliff-nav-item<?php echo $i === 0 ? ' is-active' : ''; ?>" data-step="<?php echo $i; ?>">
                            <span class="cliff-nav-number"><?php echo $i + 1; ?></span>
                            <span class="cliff-nav-check">&#10003;</span>
                            <span class="cliff-nav-label"><?php echo esc_html($label); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="cliff-content">
                <div class="cliff-header">
                    <h1 class="cliff-title"><?php echo esc_html(cliff_text('main_title')); ?></h1>
                </div>
                <div class="cliff-progress"><div class="cliff-progress-bar" style="width:0%"></div></div>

                <form id="cliff-form" class="cliff-form" enctype="multipart/form-data" novalidate>
                    <?php wp_nonce_field('cliff_form_nonce', 'cliff_nonce'); ?>

                    <?php
                    self::render_step0();
                    self::render_step_cards(1, 'konyha_kialakitas', 'kialakitas', 's1', 'radio', 3);
                    self::render_step_cards(2, 'konyha_stilusa[]', 'stilus', 's2', 'checkbox', 3);
                    self::render_step_cards(3, 'szinvilag', 'szinvilag', 's3', 'radio', 2, true);
                    self::render_step_cards(4, 'konyhapult', 'konyhapult', 's4', 'radio', 3);
                    self::render_step5();
                    self::render_step6();
                    self::render_step7();
                    self::render_step8();
                    ?>
                </form>

                <footer class="cliff-footer">
                    <?php echo wp_kses_post(cliff_text('footer_text')); ?>
                </footer>
            </div>
        </div>
        <?php
    }

    /* ---- Step 0: Email ---- */

    private static function render_step0(): void
    {
        $hero = cliff_img('s0_hero');
        ?>
        <div class="cliff-step is-active" data-step="0">
            <div class="cliff-step-layout <?php echo $hero ? 'has-hero' : ''; ?>">
                <div class="cliff-step-main">
                    <div class="cliff-step-intro">
                        <h2><?php echo esc_html(cliff_text('s0_title')); ?></h2>
                        <p><?php echo wp_kses_post(cliff_text('s0_desc1')); ?></p>
                        <p><?php echo wp_kses_post(cliff_text('s0_desc2')); ?></p>
                    </div>
                    <div class="cliff-field-group">
                        <label class="cliff-label" for="cliff-email"><?php echo esc_html(cliff_text('s0_email_label')); ?></label>
                        <input type="email" id="cliff-email" name="email" class="cliff-input"
                               placeholder="<?php echo esc_attr(cliff_text('s0_email_placeholder')); ?>" required>
                        <span class="cliff-error" id="cliff-email-error"></span>
                    </div>
                    <button type="button" class="cliff-btn cliff-btn-next" data-next="1">
                        <?php echo esc_html(cliff_text('s0_button')); ?>
                    </button>
                </div>
                <?php if ($hero): ?>
                    <div class="cliff-step-hero">
                        <img src="<?php echo esc_url($hero); ?>" alt="">
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /* ---- Generic card step (Steps 1-4) ---- */

    private static function render_step_cards(
        int $step, string $name, string $group, string $prefix,
        string $type, int $cols, bool $tall = false
    ): void {
        $options = self::get_options($group);
        $is_multi = $type === 'checkbox';
        ?>
        <div class="cliff-step" data-step="<?php echo $step; ?>">
            <h2><?php echo esc_html(cliff_text("{$prefix}_title")); ?></h2>
            <p class="cliff-step-desc"><?php echo wp_kses_post(cliff_text("{$prefix}_desc")); ?></p>
            <div class="cliff-cards cliff-cards-<?php echo $cols; ?>">
                <?php foreach ($options as $opt):
                    $key = $opt['key'];
                    $label = $opt['label'];
                    $img = $opt['image'] ?? '';
                    ?>
                    <label class="cliff-card <?php echo $is_multi ? 'cliff-card-multi' : ''; ?>" data-value="<?php echo esc_attr($key); ?>">
                        <input type="<?php echo $type; ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($key); ?>">
                        <div class="cliff-card-inner">
                            <div class="cliff-card-img <?php echo $tall ? 'cliff-card-img-tall' : ''; ?>"
                                 <?php if ($img): ?>style="background-image:url('<?php echo esc_url($img); ?>')"<?php endif; ?>>
                                <?php if (!$img): ?>
                                    <div class="cliff-card-icon">
                                        <span class="cliff-style-name"><?php echo esc_html($label); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="cliff-card-label"><?php echo esc_html($label); ?></span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($is_multi): ?>
                <button type="button" class="cliff-btn cliff-btn-next cliff-btn-manual" data-next="<?php echo $step + 1; ?>">
                    <?php echo esc_html(cliff_text("{$prefix}_button")); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }

    /* ---- Step 5: Konyhagépek ---- */

    private static function render_step5(): void
    {
        $groups = [
            'suto'        => ['field' => 'suto',        'title_key' => 's5_suto_title'],
            'huto'        => ['field' => 'huto',        'title_key' => 's5_huto_title'],
            'elszivo'     => ['field' => 'elszivo',     'title_key' => 's5_elszivo_title'],
            'fozofelulet' => ['field' => 'fozofelulet', 'title_key' => 's5_fozofelulet_title'],
            'mosogatogep' => ['field' => 'mosogatogep', 'title_key' => 's5_mosogatogep_title'],
        ];
        ?>
        <div class="cliff-step" data-step="5">
            <h2><?php echo esc_html(cliff_text('s5_title')); ?></h2>
            <p class="cliff-step-desc"><?php echo wp_kses_post(cliff_text('s5_desc')); ?></p>

            <div class="cliff-appliance-grid">
                <?php foreach ($groups as $groupKey => $g):
                    $options = self::get_options($groupKey);
                    $bg = cliff_img("s5_{$groupKey}");
                    $style = $bg ? 'background-image:url(' . esc_url($bg) . ');' : '';
                    ?>
                    <div class="cliff-appliance-card" style="<?php echo esc_attr($style); ?>">
                        <h3 class="cliff-appliance-card-title"><?php echo esc_html(cliff_text($g['title_key'])); ?></h3>
                        <div class="cliff-appliance-card-btns">
                            <?php foreach ($options as $opt): ?>
                                <label class="cliff-btn-option">
                                    <input type="radio" name="<?php echo esc_attr($g['field']); ?>" value="<?php echo esc_attr($opt['key']); ?>">
                                    <span class="cliff-btn-option-label"><?php echo esc_html($opt['label']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="cliff-btn cliff-btn-next cliff-btn-manual" data-next="6">
                <?php echo esc_html(cliff_text('s5_button')); ?>
            </button>
        </div>
        <?php
    }

    /* ---- Step 6: Adatok ---- */

    private static function render_step6(): void
    {
        $types = self::get_options('ingatlan');
        ?>
        <div class="cliff-step" data-step="6">
            <h2><?php echo esc_html(cliff_text('s6_title')); ?></h2>
            <p class="cliff-step-desc"><?php echo wp_kses_post(cliff_text('s6_desc')); ?></p>

            <div class="cliff-field-group">
                <label class="cliff-label"><?php echo esc_html(cliff_text('s6_ingatlan_label')); ?></label>
                <div class="cliff-btn-group cliff-btn-group-wide">
                    <?php foreach ($types as $opt): ?>
                        <label class="cliff-btn-option">
                            <input type="radio" name="ingatlan_tipus" value="<?php echo esc_attr($opt['key']); ?>">
                            <span class="cliff-btn-option-label"><?php echo esc_html($opt['label']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cliff-field-group">
                <label class="cliff-label" for="cliff-meret"><?php echo esc_html(cliff_text('s6_meret_label')); ?></label>
                <input type="text" id="cliff-meret" name="konyha_meret" class="cliff-input"
                       placeholder="<?php echo esc_attr(cliff_text('s6_meret_placeholder')); ?>">
            </div>

            <div class="cliff-upload-row">
                <div class="cliff-upload-group">
                    <label class="cliff-label"><?php echo esc_html(cliff_text('s6_alaprajz_label')); ?></label>
                    <label class="cliff-upload-btn" for="cliff-alaprajz">
                        <span class="cliff-upload-icon">&#128206;</span>
                        <span class="cliff-upload-text" id="cliff-alaprajz-text">fájl kiválasztása</span>
                        <input type="file" id="cliff-alaprajz" name="alaprajz" accept="image/*,.pdf,.dwg" class="cliff-file-input">
                    </label>
                </div>
                <div class="cliff-upload-group">
                    <label class="cliff-label"><?php echo esc_html(cliff_text('s6_foto_label')); ?></label>
                    <label class="cliff-upload-btn" for="cliff-foto">
                        <span class="cliff-upload-icon">&#128247;</span>
                        <span class="cliff-upload-text" id="cliff-foto-text">fájl kiválasztása</span>
                        <input type="file" id="cliff-foto" name="foto" accept="image/*,.pdf" class="cliff-file-input">
                    </label>
                </div>
            </div>

            <button type="button" class="cliff-btn cliff-btn-next cliff-btn-manual" data-next="7">
                <?php echo esc_html(cliff_text('s6_button')); ?>
            </button>
        </div>
        <?php
    }

    /* ---- Step 7: Időpont, elérhetőség ---- */

    private static function render_step7(): void
    {
        $timings = self::get_options('idopont');
        $privacy_url = get_option('cliff_form_privacy_url', '#');
        $adatvedelem_text = str_replace('{{link}}', esc_url($privacy_url), cliff_text('s7_adatvedelem'));
        $hero = cliff_img('s7_hero');
        ?>
        <div class="cliff-step" data-step="7">
            <div class="cliff-step-layout <?php echo $hero ? 'has-hero' : ''; ?>">
                <div class="cliff-step-main">
                    <h2><?php echo esc_html(cliff_text('s7_title')); ?></h2>
                    <div class="cliff-step-intro">
                        <p><?php echo wp_kses_post(cliff_text('s7_intro')); ?></p>
                        <p class="cliff-note"><?php echo wp_kses_post(cliff_text('s7_note')); ?></p>
                    </div>

                    <div class="cliff-fields-row">
                        <div class="cliff-field-group cliff-field-half">
                            <label class="cliff-label" for="cliff-telefon"><?php echo esc_html(cliff_text('s7_telefon_label')); ?></label>
                            <input type="tel" id="cliff-telefon" name="telefonszam" class="cliff-input"
                                   placeholder="<?php echo esc_attr(cliff_text('s7_telefon_placeholder')); ?>">
                        </div>
                        <div class="cliff-field-group cliff-field-half">
                            <label class="cliff-label" for="cliff-irsz"><?php echo esc_html(cliff_text('s7_irsz_label')); ?></label>
                            <input type="text" id="cliff-irsz" name="iranyitoszam" class="cliff-input"
                                   placeholder="<?php echo esc_attr(cliff_text('s7_irsz_placeholder')); ?>">
                        </div>
                    </div>

                    <div class="cliff-field-group">
                        <label class="cliff-label"><?php echo esc_html(cliff_text('s7_idopont_label')); ?></label>
                        <div class="cliff-btn-group cliff-btn-group-stack">
                            <?php foreach ($timings as $opt): ?>
                                <label class="cliff-btn-option">
                                    <input type="radio" name="idopont" value="<?php echo esc_attr($opt['key']); ?>">
                                    <span class="cliff-btn-option-label"><?php echo esc_html($opt['label']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="cliff-consent-group">
                        <label class="cliff-checkbox">
                            <input type="checkbox" name="adatvedelem" value="1" required>
                            <span class="cliff-checkmark"></span>
                            <span><?php echo wp_kses_post($adatvedelem_text); ?></span>
                        </label>
                        <label class="cliff-checkbox">
                            <input type="checkbox" name="marketing" value="1">
                            <span class="cliff-checkmark"></span>
                            <span><?php echo wp_kses_post(cliff_text('s7_marketing')); ?></span>
                        </label>
                    </div>

                    <button type="button" class="cliff-btn cliff-btn-submit" id="cliff-submit">
                        <?php echo esc_html(cliff_text('s7_button')); ?>
                    </button>
                    <div class="cliff-loading" id="cliff-loading" style="display:none;">
                        <span class="cliff-spinner"></span> Küldés folyamatban...
                    </div>
                </div>
                <?php if ($hero): ?>
                    <div class="cliff-step-hero">
                        <img src="<?php echo esc_url($hero); ?>" alt="">
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /* ---- Step 8: Köszönjük ---- */

    private static function render_step8(): void
    {
        $hero = cliff_img('s8_hero');
        ?>
        <div class="cliff-step cliff-step-thanks" data-step="8">
            <div class="cliff-step-layout <?php echo $hero ? 'has-hero' : ''; ?>">
                <div class="cliff-step-main">
                    <div class="cliff-thanks">
                        <div class="cliff-thanks-icon">&#10003;</div>
                        <h2><?php echo esc_html(cliff_text('s8_title')); ?></h2>
                        <p><?php echo wp_kses_post(cliff_text('s8_body')); ?></p>
                        <div class="cliff-thanks-contact">
                            <p><?php echo wp_kses_post(cliff_text('s8_contact_intro')); ?></p>
                            <p><strong>Telefon:</strong> <?php echo esc_html(cliff_text('s8_telefon')); ?></p>
                            <p><strong>E-mail:</strong> <?php echo esc_html(cliff_text('s8_email')); ?></p>
                        </div>
                        <div class="cliff-thanks-cities">
                            <?php echo esc_html(cliff_text('s8_cities')); ?>
                        </div>
                    </div>
                </div>
                <?php if ($hero): ?>
                    <div class="cliff-step-hero">
                        <img src="<?php echo esc_url($hero); ?>" alt="">
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
