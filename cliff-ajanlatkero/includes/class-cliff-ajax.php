<?php

if (!defined('ABSPATH')) {
    exit;
}

class Cliff_Ajax
{
    public static function init(): void
    {
        add_action('wp_ajax_cliff_submit_form', [self::class, 'handle_submit']);
        add_action('wp_ajax_nopriv_cliff_submit_form', [self::class, 'handle_submit']);
    }

    public static function handle_submit(): void
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cliff_form_nonce')) {
            wp_send_json_error(['message' => 'Biztonsági hiba. Kérjük frissítse az oldalt!']);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Érvénytelen e-mail cím!']);
        }

        // Collect all form fields into a flexible JSON structure
        $skip_keys = ['action', 'nonce', 'cliff_nonce', '_wp_http_referer', 'email', 'alaprajz', 'foto'];
        $form_data = [];

        foreach ($_POST as $key => $value) {
            if (in_array($key, $skip_keys, true)) {
                continue;
            }
            if (is_array($value)) {
                $form_data[$key] = implode(', ', array_map('sanitize_text_field', $value));
            } else {
                $form_data[$key] = sanitize_text_field($value);
            }
        }

        // Handle file uploads
        $alaprajz_url = self::handle_file_upload('alaprajz');
        $foto_url = self::handle_file_upload('foto');

        // Save to database
        global $wpdb;
        $table = $wpdb->prefix . 'cliff_ajanlat';

        $result = $wpdb->insert($table, [
            'email'       => $email,
            'form_data'   => wp_json_encode($form_data, JSON_UNESCAPED_UNICODE),
            'alaprajz_url' => $alaprajz_url,
            'foto_url'    => $foto_url,
        ]);

        if ($result === false) {
            wp_send_json_error(['message' => 'Adatbázis hiba. Kérjük próbálja újra!']);
        }

        // Send email notifications
        self::send_notification_email($email, $form_data, $alaprajz_url, $foto_url);

        wp_send_json_success(['message' => 'Sikeres küldés!']);
    }

    private static function handle_file_upload(string $field): string
    {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $uploaded = wp_handle_upload($_FILES[$field], [
            'test_form' => false,
            'mimes'     => [
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
                'gif'      => 'image/gif',
                'webp'     => 'image/webp',
                'pdf'      => 'application/pdf',
            ],
        ]);

        return $uploaded['url'] ?? '';
    }

    private static function send_notification_email(string $email, array $form_data, string $alaprajz_url, string $foto_url): void
    {
        $to = get_option('cliff_form_email', get_option('admin_email'));
        $subject = 'Új Cliff ajánlatkérés - ' . $email;

        // Build nice labels from field keys
        $label_map = [
            'konyha_kialakitas' => 'Konyha kialakítása',
            'konyha_stilusa'    => 'Konyha stílusa',
            'szinvilag'         => 'Színvilág',
            'konyhapult'        => 'Konyhapult',
            'suto'              => 'Sütő',
            'huto'              => 'Hűtő',
            'elszivo'           => 'Elszívó',
            'fozofelulet'       => 'Főzőfelület',
            'mosogatogep'       => 'Mosogatógép',
            'ingatlan_tipus'    => 'Ingatlan típusa',
            'konyha_meret'      => 'Konyha méret',
            'telefonszam'       => 'Telefonszám',
            'iranyitoszam'      => 'Irányítószám',
            'idopont'           => 'Időpont',
            'adatvedelem'       => 'Adatvédelem elfogadva',
            'marketing'         => 'Marketing hozzájárulás',
        ];

        $body = "<html><body>";
        $body .= "<h2 style='color:#1a1a1a;'>Új Cliff Online Ajánlatkérés</h2>";
        $body .= "<table style='border-collapse:collapse;width:100%;max-width:600px;'>";

        $body .= "<tr><td style='padding:10px 15px;border-bottom:1px solid #eee;font-weight:bold;width:200px;color:#333;'>E-mail</td>";
        $body .= "<td style='padding:10px 15px;border-bottom:1px solid #eee;color:#555;'>" . esc_html($email) . "</td></tr>";

        foreach ($form_data as $key => $value) {
            $label = $label_map[$key] ?? ucfirst(str_replace(['_', '-'], ' ', $key));
            if (empty($value)) {
                continue;
            }
            $body .= "<tr><td style='padding:10px 15px;border-bottom:1px solid #eee;font-weight:bold;color:#333;'>" . esc_html($label) . "</td>";
            $body .= "<td style='padding:10px 15px;border-bottom:1px solid #eee;color:#555;'>" . esc_html($value) . "</td></tr>";
        }

        if ($alaprajz_url) {
            $body .= "<tr><td style='padding:10px 15px;border-bottom:1px solid #eee;font-weight:bold;color:#333;'>Alaprajz</td>";
            $body .= "<td style='padding:10px 15px;border-bottom:1px solid #eee;'><a href='" . esc_url($alaprajz_url) . "'>Megtekintés</a></td></tr>";
        }
        if ($foto_url) {
            $body .= "<tr><td style='padding:10px 15px;border-bottom:1px solid #eee;font-weight:bold;color:#333;'>Fotó/Skicc</td>";
            $body .= "<td style='padding:10px 15px;border-bottom:1px solid #eee;'><a href='" . esc_url($foto_url) . "'>Megtekintés</a></td></tr>";
        }

        $body .= "</table>";
        $body .= "<p style='color:#888;font-size:12px;margin-top:20px;'>Beérkezett: " . current_time('Y-m-d H:i:s') . "</p>";
        $body .= "</body></html>";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Cliff Ajánlatkérés <' . get_option('admin_email') . '>',
            'Reply-To: ' . $email,
        ];

        wp_mail($to, $subject, $body, $headers);

        // Confirmation to customer
        $cc_subject = 'Cliff Konyhák - Ajánlatkérés visszaigazolás';
        $cc_body = "<html><body>";
        $cc_body .= "<h2 style='color:#1a1a1a;'>" . esc_html(cliff_text('s8_title')) . "</h2>";
        $cc_body .= "<p>Tisztelt Ügyfelünk!</p>";
        $cc_body .= "<p>" . esc_html(cliff_text('s8_body')) . "</p>";
        $cc_body .= "<p>Üdvözlettel,<br><strong>Cliff Konyhák</strong></p>";
        $cc_body .= "<p style='color:#888;font-size:12px;'>Telefon: " . esc_html(cliff_text('s8_telefon')) . " | E-mail: " . esc_html(cliff_text('s8_email')) . "</p>";
        $cc_body .= "</body></html>";

        wp_mail($email, $cc_subject, $cc_body, [
            'Content-Type: text/html; charset=UTF-8',
            'From: Cliff Konyhák <' . get_option('admin_email') . '>',
        ]);
    }
}
