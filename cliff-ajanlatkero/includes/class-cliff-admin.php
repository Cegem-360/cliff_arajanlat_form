<?php

if (!defined('ABSPATH')) {
    exit;
}

class Cliff_Admin
{
    public static function init(): void
    {
        if (!is_admin()) {
            return;
        }

        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if (strpos($hook, 'cliff-ajanlatkero') === false) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('cliff-admin', CLIFF_FORM_URL . 'assets/css/cliff-admin.css', [], CLIFF_FORM_VERSION);
    }

    public static function add_menu(): void
    {
        add_menu_page(
            'Cliff Ajánlatkérések',
            'Cliff Ajánlat',
            'manage_options',
            'cliff-ajanlatkero',
            [self::class, 'render_submissions_page'],
            'dashicons-clipboard',
            30
        );

        add_submenu_page(
            'cliff-ajanlatkero',
            'Ajánlatkérések',
            'Ajánlatkérések',
            'manage_options',
            'cliff-ajanlatkero',
            [self::class, 'render_submissions_page']
        );

        add_submenu_page(
            'cliff-ajanlatkero',
            'Szövegek és képek',
            'Szövegek és képek',
            'manage_options',
            'cliff-ajanlatkero-content',
            [self::class, 'render_content_page']
        );

        add_submenu_page(
            'cliff-ajanlatkero',
            'Opciók kezelése',
            'Opciók kezelése',
            'manage_options',
            'cliff-ajanlatkero-options',
            [self::class, 'render_options_page']
        );

        add_submenu_page(
            'cliff-ajanlatkero',
            'Beállítások',
            'Beállítások',
            'manage_options',
            'cliff-ajanlatkero-settings',
            [self::class, 'render_settings_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting('cliff_form_settings', 'cliff_form_email', [
            'sanitize_callback' => 'sanitize_email',
        ]);
        register_setting('cliff_form_settings', 'cliff_form_logo', [
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_setting('cliff_form_settings', 'cliff_form_privacy_url', [
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_setting('cliff_form_content', 'cliff_form_content', [
            'sanitize_callback' => [self::class, 'sanitize_content'],
        ]);
        register_setting('cliff_form_options_group', 'cliff_form_options', [
            'sanitize_callback' => [self::class, 'sanitize_options'],
        ]);
    }

    public static function sanitize_options($input): array
    {
        if (!is_array($input)) {
            return Cliff_Form::default_options();
        }

        $sanitized = [];
        foreach ($input as $group => $items) {
            $group = sanitize_key($group);
            if (!is_array($items)) {
                continue;
            }

            $sanitized[$group] = [];
            foreach ($items as $item) {
                if (empty($item['key'])) {
                    continue;
                }
                $sanitized[$group][] = [
                    'key'   => sanitize_title($item['key']),
                    'label' => sanitize_text_field($item['label'] ?? ''),
                    'image' => esc_url_raw($item['image'] ?? ''),
                ];
            }
        }

        return $sanitized;
    }

    public static function sanitize_content($input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $sanitized = [];
        foreach ($input as $key => $value) {
            if (str_starts_with($key, 'img_')) {
                $sanitized[$key] = esc_url_raw($value);
            } else {
                $sanitized[$key] = wp_kses_post($value);
            }
        }

        return $sanitized;
    }

    /* =========================================
       All Content (Texts + Images) - Tabbed
       ========================================= */

    public static function render_content_page(): void
    {
        $c = get_option('cliff_form_content', []);
        $d = Cliff_Form::defaults();

        $active_tab = sanitize_text_field($_GET['tab'] ?? 'general');

        $tabs = [
            'general'  => 'Általános',
            'step0'    => '1. E-mail',
            'step1'    => '2. Konyha kialakítása',
            'step2'    => '3. Konyha stílusa',
            'step3'    => '4. Színvilág',
            'step4'    => '5. Konyhapult',
            'step5'    => '6. Konyhagépek',
            'step6'    => '7. Adatok',
            'step7'    => '8. Időpont',
            'step8'    => '9. Köszönjük',
        ];

        ?>
        <div class="wrap">
            <h1>Cliff Ajánlatkérő - Szövegek és képek</h1>
            <p class="description">Minden szöveg és kép szerkeszthető. Ha üresen hagyod, az alapértelmezett érték jelenik meg.</p>

            <nav class="nav-tab-wrapper cliff-tabs">
                <?php foreach ($tabs as $slug => $label): ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $slug)); ?>"
                       class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php" class="cliff-content-form">
                <?php settings_fields('cliff_form_content'); ?>

                <?php
                // Always output all fields as hidden so they persist across tabs
                self::output_hidden_fields($c, $active_tab);

                switch ($active_tab) {
                    case 'general':
                        self::render_tab_general($c, $d);
                        break;
                    case 'step0':
                        self::render_tab_step0($c, $d);
                        break;
                    case 'step1':
                        self::render_tab_step1($c, $d);
                        break;
                    case 'step2':
                        self::render_tab_step2($c, $d);
                        break;
                    case 'step3':
                        self::render_tab_step3($c, $d);
                        break;
                    case 'step4':
                        self::render_tab_step4($c, $d);
                        break;
                    case 'step5':
                        self::render_tab_step5($c, $d);
                        break;
                    case 'step6':
                        self::render_tab_step6($c, $d);
                        break;
                    case 'step7':
                        self::render_tab_step7($c, $d);
                        break;
                    case 'step8':
                        self::render_tab_step8($c, $d);
                        break;
                }
                ?>

                <?php submit_button('Mentés'); ?>
            </form>
        </div>

        <script>
        function cliffSelectMedia(inputId) {
            var frame = wp.media({ multiple: false });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                document.getElementById(inputId).value = attachment.url;
                var preview = document.getElementById(inputId + '-preview');
                if (preview) {
                    preview.src = attachment.url;
                    preview.style.display = 'block';
                }
            });
            frame.open();
        }
        function cliffClearMedia(inputId) {
            document.getElementById(inputId).value = '';
            var preview = document.getElementById(inputId + '-preview');
            if (preview) preview.style.display = 'none';
        }
        </script>
        <?php
    }

    /**
     * Output all existing content fields as hidden inputs so
     * switching tabs doesn't lose data from other tabs.
     */
    private static function output_hidden_fields(array $c, string $active_tab): void
    {
        $d = Cliff_Form::defaults();
        $tab_prefixes = [
            'general' => ['main_', 'footer_', 'nav_'],
            'step0'   => ['s0_'],
            'step1'   => ['s1_'],
            'step2'   => ['s2_'],
            'step3'   => ['s3_'],
            'step4'   => ['s4_'],
            'step5'   => ['s5_'],
            'step6'   => ['s6_'],
            'step7'   => ['s7_'],
            'step8'   => ['s8_'],
        ];

        $active_prefixes = $tab_prefixes[$active_tab] ?? [];

        // Output hidden fields for all keys NOT in the active tab
        foreach ($d as $key => $default) {
            $is_active = false;
            foreach ($active_prefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $is_active = true;
                    break;
                }
            }
            if (!$is_active) {
                $val = $c[$key] ?? '';
                echo '<input type="hidden" name="cliff_form_content[' . esc_attr($key) . ']" value="' . esc_attr($val) . '">';
            }
        }
    }

    /* ---- Tab renderers ---- */

    private static function render_tab_general(array $c, array $d): void
    {
        echo '<h2>Általános beállítások</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 'main_title', 'Főcím (fejléc)');
        self::textarea_field($c, $d, 'footer_text', 'Lábléc szöveg');
        echo '</table>';

        echo '<h2>Navigáció (bal oldali menü nevek)</h2>';
        echo '<table class="form-table">';
        for ($i = 0; $i <= 8; $i++) {
            self::text_field($c, $d, "nav_step{$i}", ($i + 1) . '. lépés menü neve');
        }
        echo '</table>';
    }

    private static function render_tab_step0(array $c, array $d): void
    {
        echo '<h2>1. lépés: E-mail cím</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's0_title', 'Bevezető cím');
        self::textarea_field($c, $d, 's0_desc1', 'Bevezető szöveg 1');
        self::textarea_field($c, $d, 's0_desc2', 'Bevezető szöveg 2');
        self::text_field($c, $d, 's0_email_label', 'E-mail mező felirat');
        self::text_field($c, $d, 's0_email_placeholder', 'E-mail placeholder');
        self::text_field($c, $d, 's0_button', 'Gomb felirat');
        self::image_field($c, $d, 'img_s0_hero', 'Bevezető kép (jobb oldal)');
        echo '</table>';
    }

    private static function render_tab_step1(array $c, array $d): void
    {
        echo '<h2>2. lépés: Konyha kialakítása</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's1_title', 'Lépés címe');
        self::text_field($c, $d, 's1_desc', 'Lépés leírása');
        echo '</table>';

        $options = ['egyenes', 'ketoldalon', 'l-alaku', 'u-alaku', 'g-alaku'];
        echo '<h3>Opciók szövegei és képei</h3>';
        echo '<table class="form-table">';
        foreach ($options as $opt) {
            self::text_field($c, $d, "s1_opt_{$opt}", "Felirat: {$opt}");
            self::image_field($c, $d, "img_s1_{$opt}", "Kép: {$opt}");
        }
        echo '</table>';
    }

    private static function render_tab_step2(array $c, array $d): void
    {
        echo '<h2>3. lépés: Konyha stílusa</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's2_title', 'Lépés címe');
        self::text_field($c, $d, 's2_desc', 'Lépés leírása');
        self::text_field($c, $d, 's2_button', 'Tovább gomb felirat');
        echo '</table>';

        $options = ['classic', 'country', 'jazz', 'pop', 'swing', 'electro'];
        echo '<h3>Opciók szövegei és képei</h3>';
        echo '<table class="form-table">';
        foreach ($options as $opt) {
            self::text_field($c, $d, "s2_opt_{$opt}", "Felirat: {$opt}");
            self::image_field($c, $d, "img_s2_{$opt}", "Kép: {$opt}");
        }
        echo '</table>';
    }

    private static function render_tab_step3(array $c, array $d): void
    {
        echo '<h2>4. lépés: Színvilág</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's3_title', 'Lépés címe');
        self::text_field($c, $d, 's3_desc', 'Lépés leírása');
        echo '</table>';

        $options = ['vilagos', 'sotet'];
        echo '<h3>Opciók szövegei és képei</h3>';
        echo '<table class="form-table">';
        foreach ($options as $opt) {
            self::text_field($c, $d, "s3_opt_{$opt}", "Felirat: {$opt}");
            self::image_field($c, $d, "img_s3_{$opt}", "Kép: {$opt}");
        }
        echo '</table>';
    }

    private static function render_tab_step4(array $c, array $d): void
    {
        echo '<h2>5. lépés: Konyhapult</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's4_title', 'Lépés címe');
        self::text_field($c, $d, 's4_desc', 'Lépés leírása');
        echo '</table>';

        $options = ['natur-ko', 'keramia', 'kvarc', 'laminalt', 'tomorfa'];
        echo '<h3>Opciók szövegei és képei</h3>';
        echo '<table class="form-table">';
        foreach ($options as $opt) {
            self::text_field($c, $d, "s4_opt_{$opt}", "Felirat: {$opt}");
            self::image_field($c, $d, "img_s4_{$opt}", "Kép: {$opt}");
        }
        echo '</table>';
    }

    private static function render_tab_step5(array $c, array $d): void
    {
        echo '<h2>6. lépés: Konyhagépek</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's5_title', 'Lépés címe');
        self::text_field($c, $d, 's5_desc', 'Lépés leírása');
        self::text_field($c, $d, 's5_button', 'Tovább gomb felirat');
        echo '</table>';

        $groups = [
            'suto'        => ['title_key' => 's5_suto_title', 'opts' => ['szemmagassagban', 'alul']],
            'huto'        => ['title_key' => 's5_huto_title', 'opts' => ['beepitett', 'szabadon-allo']],
            'elszivo'     => ['title_key' => 's5_elszivo_title', 'opts' => ['szabadon-allo', 'beepitett']],
            'fozofelulet' => ['title_key' => 's5_fozofelulet_title', 'opts' => ['gaz', 'keramia', 'indukcios']],
            'mosogatogep' => ['title_key' => 's5_mosogatogep_title', 'opts' => ['teljesen-integralt', 'latszo-kezelofelulet']],
        ];

        foreach ($groups as $groupKey => $group) {
            echo '<h3>' . esc_html(ucfirst(str_replace('_', ' ', $groupKey))) . '</h3>';
            echo '<table class="form-table">';
            self::text_field($c, $d, $group['title_key'], 'Csoport neve');
            foreach ($group['opts'] as $opt) {
                self::text_field($c, $d, "s5_{$groupKey}_{$opt}", "Opció: {$opt}");
            }
            self::image_field($c, $d, "img_s5_{$groupKey}", "Kép: {$groupKey}");
            echo '</table>';
        }
    }

    private static function render_tab_step6(array $c, array $d): void
    {
        echo '<h2>7. lépés: Adatok</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's6_title', 'Lépés címe');
        self::text_field($c, $d, 's6_desc', 'Lépés leírása');
        self::text_field($c, $d, 's6_ingatlan_label', 'Ingatlan típus felirat');
        self::text_field($c, $d, 's6_button', 'Tovább gomb felirat');
        echo '</table>';

        echo '<h3>Ingatlan típusok</h3>';
        echo '<table class="form-table">';
        $types = ['csaladi-haz', 'lakopark', 'tarsas-haz', 'nyaralo'];
        foreach ($types as $t) {
            self::text_field($c, $d, "s6_type_{$t}", "Típus: {$t}");
        }
        echo '</table>';

        echo '<h3>Konyha méret és feltöltés</h3>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's6_meret_label', 'Konyha méret felirat');
        self::text_field($c, $d, 's6_meret_placeholder', 'Konyha méret placeholder');
        self::text_field($c, $d, 's6_alaprajz_label', 'Alaprajz felirat');
        self::text_field($c, $d, 's6_foto_label', 'Fotó/skicc felirat');
        self::image_field($c, $d, 'img_s6_bg', 'Háttérkép');
        echo '</table>';
    }

    private static function render_tab_step7(array $c, array $d): void
    {
        echo '<h2>8. lépés: Időpont, elérhetőség</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's7_title', 'Lépés címe');
        self::textarea_field($c, $d, 's7_intro', 'Bevezető szöveg');
        self::text_field($c, $d, 's7_note', 'Megjegyzés szöveg');
        self::text_field($c, $d, 's7_telefon_label', 'Telefonszám felirat');
        self::text_field($c, $d, 's7_telefon_placeholder', 'Telefonszám placeholder');
        self::text_field($c, $d, 's7_irsz_label', 'Irányítószám felirat');
        self::text_field($c, $d, 's7_irsz_placeholder', 'Irányítószám placeholder');
        self::text_field($c, $d, 's7_idopont_label', 'Időpont kérdés');
        echo '</table>';

        echo '<h3>Időpont opciók</h3>';
        echo '<table class="form-table">';
        $timings = ['1-2-honap', '4-5-honap', 'erdeklodok'];
        foreach ($timings as $t) {
            self::text_field($c, $d, "s7_time_{$t}", "Opció: {$t}");
        }
        echo '</table>';

        echo '<h3>Hozzájárulások és gomb</h3>';
        echo '<table class="form-table">';
        self::textarea_field($c, $d, 's7_adatvedelem', 'Adatvédelmi szöveg (HTML engedélyezett, {{link}} = adatvédelmi link)');
        self::textarea_field($c, $d, 's7_marketing', 'Marketing hozzájárulás szöveg');
        self::text_field($c, $d, 's7_button', 'Küldés gomb felirat');
        self::image_field($c, $d, 'img_s7_hero', 'Oldalsó kép');
        echo '</table>';
    }

    private static function render_tab_step8(array $c, array $d): void
    {
        echo '<h2>9. Köszönjük oldal</h2>';
        echo '<table class="form-table">';
        self::text_field($c, $d, 's8_title', 'Köszönjük cím');
        self::textarea_field($c, $d, 's8_body', 'Köszönjük szöveg');
        self::text_field($c, $d, 's8_contact_intro', 'Elérhetőség bevezető');
        self::text_field($c, $d, 's8_telefon', 'Telefon szám');
        self::text_field($c, $d, 's8_email', 'E-mail cím');
        self::text_field($c, $d, 's8_cities', 'Városok felsorolás');
        self::image_field($c, $d, 'img_s8_hero', 'Köszönjük kép');
        echo '</table>';
    }

    /* ---- Field helpers ---- */

    private static function text_field(array $c, array $d, string $key, string $label): void
    {
        $val = $c[$key] ?? '';
        $default = $d[$key] ?? '';
        ?>
        <tr>
            <th><label for="cf-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <input type="text" id="cf-<?php echo esc_attr($key); ?>"
                       name="cliff_form_content[<?php echo esc_attr($key); ?>]"
                       value="<?php echo esc_attr($val); ?>"
                       class="large-text"
                       placeholder="<?php echo esc_attr($default); ?>">
                <p class="description">Alapértelmezett: <code><?php echo esc_html($default); ?></code></p>
            </td>
        </tr>
        <?php
    }

    private static function textarea_field(array $c, array $d, string $key, string $label): void
    {
        $val = $c[$key] ?? '';
        $default = $d[$key] ?? '';
        ?>
        <tr>
            <th><label for="cf-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <textarea id="cf-<?php echo esc_attr($key); ?>"
                          name="cliff_form_content[<?php echo esc_attr($key); ?>]"
                          class="large-text" rows="3"
                          placeholder="<?php echo esc_attr($default); ?>"><?php echo esc_textarea($val); ?></textarea>
                <p class="description">Alapértelmezett: <code><?php echo esc_html($default); ?></code></p>
            </td>
        </tr>
        <?php
    }

    private static function image_field(array $c, array $d, string $key, string $label): void
    {
        $val = $c[$key] ?? '';
        ?>
        <tr>
            <th><?php echo esc_html($label); ?></th>
            <td>
                <input type="url" id="cf-<?php echo esc_attr($key); ?>"
                       name="cliff_form_content[<?php echo esc_attr($key); ?>]"
                       value="<?php echo esc_attr($val); ?>"
                       class="regular-text">
                <button type="button" class="button" onclick="cliffSelectMedia('cf-<?php echo esc_attr($key); ?>')">Kiválasztás</button>
                <button type="button" class="button cliff-btn-clear" onclick="cliffClearMedia('cf-<?php echo esc_attr($key); ?>')">Törlés</button>
                <br>
                <img id="cf-<?php echo esc_attr($key); ?>-preview"
                     src="<?php echo esc_url($val); ?>"
                     style="max-width:200px;max-height:120px;margin-top:8px;border-radius:4px;border:1px solid #ddd;<?php echo $val ? '' : 'display:none;'; ?>">
            </td>
        </tr>
        <?php
    }

    /* =========================================
       Settings Page (Email, Logo, Privacy URL)
       ========================================= */

    public static function render_settings_page(): void
    {
        ?>
        <div class="wrap">
            <h1>Cliff Ajánlatkérő - Beállítások</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cliff_form_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Értesítési e-mail cím</th>
                        <td>
                            <input type="email" name="cliff_form_email" class="regular-text"
                                   value="<?php echo esc_attr(get_option('cliff_form_email', get_option('admin_email'))); ?>">
                            <p class="description">Ide érkeznek az ajánlatkérés értesítések.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Logó URL</th>
                        <td>
                            <input type="url" name="cliff_form_logo" class="regular-text" id="cliff-logo-url"
                                   value="<?php echo esc_attr(get_option('cliff_form_logo', '')); ?>">
                            <button type="button" class="button" onclick="cliffSelectMediaSimple('cliff-logo-url')">Kiválasztás</button>
                            <p class="description">A wizard bal felső sarkában megjelenő logó.</p>
                            <?php if ($logo = get_option('cliff_form_logo')): ?>
                                <br><img src="<?php echo esc_url($logo); ?>" style="max-width:150px;margin-top:8px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Adatvédelmi tájékoztató URL</th>
                        <td>
                            <input type="url" name="cliff_form_privacy_url" class="regular-text"
                                   value="<?php echo esc_attr(get_option('cliff_form_privacy_url', '')); ?>">
                            <p class="description">Az adatvédelmi tájékoztató oldal linkje.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Mentés'); ?>
            </form>

            <hr>
            <h2>Shortcode</h2>
            <p>Helyezd el az alábbi shortcode-ot bármelyik oldalra:</p>
            <code style="font-size:16px;padding:10px 20px;display:inline-block;background:#f0f0f0;">[cliff_ajanlatkero]</code>
        </div>

        <script>
        function cliffSelectMediaSimple(inputId) {
            var frame = wp.media({ multiple: false });
            frame.on('select', function() {
                document.getElementById(inputId).value = frame.state().get('selection').first().toJSON().url;
            });
            frame.open();
        }
        </script>
        <?php
    }

    /* =========================================
       Submissions List
       ========================================= */

    public static function render_submissions_page(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cliff_ajanlat';

        if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'cliff_delete_' . $_GET['delete'])) {
                $wpdb->delete($table, ['id' => intval($_GET['delete'])]);
                echo '<div class="notice notice-success"><p>Ajánlatkérés törölve.</p></div>';
            }
        }

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            self::export_csv();
            return;
        }

        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $items = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset)
        );

        ?>
        <div class="wrap">
            <h1>Cliff Ajánlatkérések
                <a href="<?php echo esc_url(add_query_arg('export', 'csv')); ?>" class="page-title-action">CSV Export</a>
            </h1>
            <p class="description">Shortcode: <code>[cliff_ajanlatkero]</code> - Összesen: <strong><?php echo $total; ?></strong> ajánlatkérés</p>

            <?php if (empty($items)): ?>
                <div class="notice notice-info"><p>Még nincsenek ajánlatkérések.</p></div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>E-mail</th>
                            <th>Dátum</th>
                            <th style="width:120px">Művelet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td><strong><?php echo esc_html($item->email); ?></strong></td>
                                <td><?php echo esc_html($item->created_at); ?></td>
                                <td>
                                    <a href="#" onclick="document.getElementById('cliff-detail-<?php echo $item->id; ?>').style.display='table-row'; return false;">Részletek</a>
                                    |
                                    <a href="<?php echo wp_nonce_url(add_query_arg('delete', $item->id), 'cliff_delete_' . $item->id); ?>"
                                       onclick="return confirm('Biztosan törli?');" style="color:#a00;">Törlés</a>
                                </td>
                            </tr>
                            <tr id="cliff-detail-<?php echo $item->id; ?>" style="display:none;background:#f9f9f9;">
                                <td colspan="4">
                                    <div class="cliff-detail-box">
                                        <?php self::render_detail_row($item); ?>
                                        <button type="button" class="button" onclick="this.closest('tr').style.display='none'">Bezárás</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $total_pages = ceil($total / $per_page);
                if ($total_pages > 1):
                    echo '<div class="tablenav"><div class="tablenav-pages">';
                    echo paginate_links([
                        'base'    => add_query_arg('paged', '%#%'),
                        'format'  => '',
                        'current' => $page,
                        'total'   => $total_pages,
                    ]);
                    echo '</div></div>';
                endif;
                ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_detail_row(object $item): void
    {
        $data = json_decode($item->form_data, true) ?: [];

        echo '<table class="cliff-detail-table">';
        echo '<tr><td><strong>E-mail:</strong></td><td>' . esc_html($item->email) . '</td></tr>';

        foreach ($data as $key => $value) {
            $label = ucfirst(str_replace(['_', '-'], ' ', $key));
            echo '<tr><td><strong>' . esc_html($label) . ':</strong></td><td>' . esc_html($value ?: '-') . '</td></tr>';
        }

        if ($item->alaprajz_url) {
            echo '<tr><td><strong>Alaprajz:</strong></td><td><a href="' . esc_url($item->alaprajz_url) . '" target="_blank">Megtekintés</a></td></tr>';
        }
        if ($item->foto_url) {
            echo '<tr><td><strong>Fotó/Skicc:</strong></td><td><a href="' . esc_url($item->foto_url) . '" target="_blank">Megtekintés</a></td></tr>';
        }
        echo '</table>';
    }

    /* =========================================
       Options Page - Repeater fields
       ========================================= */

    public static function render_options_page(): void
    {
        $all_options = get_option('cliff_form_options', Cliff_Form::default_options());

        $groups = [
            'kialakitas'  => ['title' => 'Konyha kialakítása',       'has_image' => true],
            'stilus'       => ['title' => 'Konyha stílusa',           'has_image' => true],
            'szinvilag'    => ['title' => 'Színvilág',                'has_image' => true],
            'konyhapult'   => ['title' => 'Konyhapult',               'has_image' => true],
            'suto'         => ['title' => 'Sütő opciók',             'has_image' => false],
            'huto'         => ['title' => 'Hűtő opciók',             'has_image' => false],
            'elszivo'      => ['title' => 'Elszívó opciók',          'has_image' => false],
            'fozofelulet'  => ['title' => 'Főzőfelület opciók',      'has_image' => false],
            'mosogatogep'  => ['title' => 'Mosogatógép opciók',      'has_image' => false],
            'ingatlan'     => ['title' => 'Ingatlan típusok',         'has_image' => false],
            'idopont'      => ['title' => 'Időpont opciók',           'has_image' => false],
        ];

        ?>
        <div class="wrap">
            <h1>Cliff Ajánlatkérő - Opciók kezelése</h1>
            <p class="description">Itt tudod bővíteni, szerkeszteni és törölni a választási lehetőségeket minden lépésben. A "Kulcs" mező az adatbázisban tárolt érték (ékezet és szóköz nélkül).</p>

            <form method="post" action="options.php">
                <?php settings_fields('cliff_form_options_group'); ?>

                <?php foreach ($groups as $groupKey => $groupInfo):
                    $items = $all_options[$groupKey] ?? [];
                    ?>
                    <div class="cliff-option-group" data-group="<?php echo esc_attr($groupKey); ?>">
                        <h2><?php echo esc_html($groupInfo['title']); ?></h2>
                        <table class="wp-list-table widefat cliff-repeater-table">
                            <thead>
                                <tr>
                                    <th style="width:30px"></th>
                                    <th style="width:180px">Kulcs (azonosító)</th>
                                    <th>Felirat</th>
                                    <?php if ($groupInfo['has_image']): ?>
                                        <th style="width:300px">Kép</th>
                                    <?php endif; ?>
                                    <th style="width:60px"></th>
                                </tr>
                            </thead>
                            <tbody class="cliff-repeater-body">
                                <?php foreach ($items as $idx => $opt): ?>
                                    <tr class="cliff-repeater-row">
                                        <td class="cliff-drag-handle">&#9776;</td>
                                        <td>
                                            <input type="text" class="widefat"
                                                   name="cliff_form_options[<?php echo esc_attr($groupKey); ?>][<?php echo $idx; ?>][key]"
                                                   value="<?php echo esc_attr($opt['key']); ?>"
                                                   placeholder="pl. egyenes">
                                        </td>
                                        <td>
                                            <input type="text" class="widefat"
                                                   name="cliff_form_options[<?php echo esc_attr($groupKey); ?>][<?php echo $idx; ?>][label]"
                                                   value="<?php echo esc_attr($opt['label']); ?>"
                                                   placeholder="Megjelenő név">
                                        </td>
                                        <?php if ($groupInfo['has_image']): ?>
                                            <td>
                                                <div class="cliff-img-field">
                                                    <input type="url" class="widefat cliff-opt-img"
                                                           id="opt-img-<?php echo esc_attr($groupKey); ?>-<?php echo $idx; ?>"
                                                           name="cliff_form_options[<?php echo esc_attr($groupKey); ?>][<?php echo $idx; ?>][image]"
                                                           value="<?php echo esc_attr($opt['image'] ?? ''); ?>">
                                                    <button type="button" class="button button-small"
                                                            onclick="cliffSelectMedia('opt-img-<?php echo esc_attr($groupKey); ?>-<?php echo $idx; ?>')">Kép</button>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <button type="button" class="button button-small cliff-remove-row" title="Törlés">&times;</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <button type="button" class="button cliff-add-row"
                                    data-group="<?php echo esc_attr($groupKey); ?>"
                                    data-has-image="<?php echo $groupInfo['has_image'] ? '1' : '0'; ?>">
                                + Új opció hozzáadása
                            </button>
                        </p>
                    </div>
                <?php endforeach; ?>

                <?php submit_button('Opciók mentése'); ?>
            </form>
        </div>

        <script>
        function cliffSelectMedia(inputId) {
            var frame = wp.media({ multiple: false, library: { type: 'image' } });
            frame.on('select', function() {
                document.getElementById(inputId).value = frame.state().get('selection').first().toJSON().url;
            });
            frame.open();
        }

        document.addEventListener('click', function(e) {
            // Remove row
            if (e.target.classList.contains('cliff-remove-row')) {
                if (confirm('Biztosan törli ezt az opciót?')) {
                    e.target.closest('.cliff-repeater-row').remove();
                    reindexAll();
                }
            }

            // Add row
            if (e.target.classList.contains('cliff-add-row')) {
                var group = e.target.dataset.group;
                var hasImage = e.target.dataset.hasImage === '1';
                var tbody = e.target.closest('.cliff-option-group').querySelector('.cliff-repeater-body');
                var idx = tbody.querySelectorAll('.cliff-repeater-row').length;

                var tr = document.createElement('tr');
                tr.className = 'cliff-repeater-row';

                var html = '<td class="cliff-drag-handle">&#9776;</td>';
                html += '<td><input type="text" class="widefat" name="cliff_form_options[' + group + '][' + idx + '][key]" placeholder="pl. uj-opcio"></td>';
                html += '<td><input type="text" class="widefat" name="cliff_form_options[' + group + '][' + idx + '][label]" placeholder="Megjelenő név"></td>';
                if (hasImage) {
                    var imgId = 'opt-img-' + group + '-' + idx;
                    html += '<td><div class="cliff-img-field"><input type="url" class="widefat cliff-opt-img" id="' + imgId + '" name="cliff_form_options[' + group + '][' + idx + '][image]" value="">';
                    html += '<button type="button" class="button button-small" onclick="cliffSelectMedia(\'' + imgId + '\')">Kép</button></div></td>';
                }
                html += '<td><button type="button" class="button button-small cliff-remove-row" title="Törlés">&times;</button></td>';

                tr.innerHTML = html;
                tbody.appendChild(tr);
            }
        });

        function reindexAll() {
            document.querySelectorAll('.cliff-option-group').forEach(function(group) {
                var groupKey = group.dataset.group;
                group.querySelectorAll('.cliff-repeater-row').forEach(function(row, idx) {
                    row.querySelectorAll('input').forEach(function(input) {
                        var name = input.name;
                        input.name = name.replace(/\[\d+\]/, '[' + idx + ']');
                        if (input.id) {
                            input.id = input.id.replace(/-\d+$/, '-' + idx);
                        }
                    });
                    // Fix media button onclick
                    var btn = row.querySelector('.button-small[onclick]');
                    if (btn) {
                        var imgInput = row.querySelector('.cliff-opt-img');
                        if (imgInput) {
                            btn.setAttribute('onclick', "cliffSelectMedia('" + imgInput.id + "')");
                        }
                    }
                });
            });
        }

        // Simple drag-and-drop sorting
        var dragSrcRow = null;
        document.addEventListener('mousedown', function(e) {
            if (e.target.classList.contains('cliff-drag-handle')) {
                dragSrcRow = e.target.closest('.cliff-repeater-row');
                dragSrcRow.setAttribute('draggable', 'true');
            }
        });
        document.addEventListener('dragstart', function(e) {
            if (dragSrcRow) {
                e.dataTransfer.effectAllowed = 'move';
                dragSrcRow.style.opacity = '0.4';
            }
        });
        document.addEventListener('dragover', function(e) {
            e.preventDefault();
            var row = e.target.closest('.cliff-repeater-row');
            if (row && row !== dragSrcRow && row.parentNode === dragSrcRow.parentNode) {
                var rect = row.getBoundingClientRect();
                var midY = rect.top + rect.height / 2;
                if (e.clientY < midY) {
                    row.parentNode.insertBefore(dragSrcRow, row);
                } else {
                    row.parentNode.insertBefore(dragSrcRow, row.nextSibling);
                }
            }
        });
        document.addEventListener('dragend', function() {
            if (dragSrcRow) {
                dragSrcRow.style.opacity = '1';
                dragSrcRow.removeAttribute('draggable');
                dragSrcRow = null;
                reindexAll();
            }
        });
        </script>
        <?php
    }

    /* =========================================
       CSV Export
       ========================================= */

    private static function export_csv(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cliff_ajanlat';
        $items = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cliff-ajanlatok-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Collect all possible field keys from all submissions
        $all_keys = [];
        $parsed = [];
        foreach ($items as $item) {
            $data = json_decode($item->form_data, true) ?: [];
            $parsed[$item->id] = $data;
            foreach (array_keys($data) as $k) {
                if (!in_array($k, $all_keys)) {
                    $all_keys[] = $k;
                }
            }
        }

        $headers = array_merge(['ID', 'E-mail'], $all_keys, ['Alaprajz', 'Fotó', 'Dátum']);
        fputcsv($output, $headers, ';');

        foreach ($items as $item) {
            $data = $parsed[$item->id];
            $row = [$item->id, $item->email];
            foreach ($all_keys as $k) {
                $row[] = $data[$k] ?? '';
            }
            $row[] = $item->alaprajz_url;
            $row[] = $item->foto_url;
            $row[] = $item->created_at;
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }
}
