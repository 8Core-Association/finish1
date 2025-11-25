<?php

/**
 * Plaćena licenca
 * (c) 2025 Tomislav Galić <tomislav@8core.hr>
 * Suradnik: Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zabranjeno ga je
 * distribuirati ili mijenjati bez izričitog dopuštenja autora.
 */

class Zaprimanje_Helper
{
    public static function registrirajZaprimanje(
        $db,
        $fk_ecm_file,
        $ID_predmeta,
        $posiljatelj_naziv,
        $datum_zaprimanja,
        $nacin_zaprimanja,
        $tip_dokumenta,
        $fk_user_creat,
        $fk_posiljatelj = null,
        $posiljatelj_broj = null,
        $fk_akt_za_prilog = null,
        $napomena = null
    ) {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_zaprimanje (
                    fk_ecm_file,
                    ID_predmeta,
                    fk_posiljatelj,
                    posiljatelj_naziv,
                    posiljatelj_broj,
                    datum_zaprimanja,
                    nacin_zaprimanja,
                    tip_dokumenta,
                    fk_akt_za_prilog,
                    napomena,
                    fk_user_creat
                ) VALUES (
                    " . (int)$fk_ecm_file . ",
                    " . (int)$ID_predmeta . ",
                    " . ($fk_posiljatelj ? (int)$fk_posiljatelj : "NULL") . ",
                    '" . $db->escape($posiljatelj_naziv) . "',
                    " . ($posiljatelj_broj ? "'" . $db->escape($posiljatelj_broj) . "'" : "NULL") . ",
                    '" . $db->escape($datum_zaprimanja) . "',
                    '" . $db->escape($nacin_zaprimanja) . "',
                    '" . $db->escape($tip_dokumenta) . "',
                    " . ($fk_akt_za_prilog ? (int)$fk_akt_za_prilog : "NULL") . ",
                    " . ($napomena ? "'" . $db->escape($napomena) . "'" : "NULL") . ",
                    " . (int)$fk_user_creat . "
                )";

        $resql = $db->query($sql);

        if ($resql) {
            return $db->last_insert_id(MAIN_DB_PREFIX . "a_zaprimanje");
        }

        return false;
    }

    public static function getZaprimanjaPoPredmetu($db, $ID_predmeta)
    {
        $zaprimanja = [];

        $sql = "SELECT
                    z.ID_zaprimanja,
                    z.fk_ecm_file,
                    z.ID_predmeta,
                    z.posiljatelj_naziv,
                    z.posiljatelj_broj,
                    z.datum_zaprimanja,
                    z.nacin_zaprimanja,
                    z.tip_dokumenta,
                    z.fk_akt_za_prilog,
                    z.napomena,
                    z.datum_kreiranja,
                    u.firstname,
                    u.lastname,
                    e.filename as doc_filename,
                    e.filepath as doc_filepath,
                    p.naziv as posiljatelj_full_naziv,
                    p.adresa as posiljatelj_adresa,
                    p.email as posiljatelj_email,
                    a.urb_broj as akt_urb_broj
                FROM " . MAIN_DB_PREFIX . "a_zaprimanje z
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON z.fk_user_creat = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files e ON z.fk_ecm_file = e.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_posiljatelji p ON z.fk_posiljatelj = p.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_akti a ON z.fk_akt_za_prilog = a.ID_akta
                WHERE z.ID_predmeta = " . (int)$ID_predmeta . "
                ORDER BY z.datum_zaprimanja DESC, z.datum_kreiranja DESC";

        $resql = $db->query($sql);

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $zaprimanja[] = $obj;
            }
        }

        return $zaprimanja;
    }

    public static function getZaprimanjaAll($db, $filters = [])
    {
        $zaprimanja = [];

        $sql = "SELECT
                    z.ID_zaprimanja,
                    z.fk_ecm_file,
                    z.ID_predmeta,
                    z.posiljatelj_naziv,
                    z.posiljatelj_broj,
                    z.datum_zaprimanja,
                    z.nacin_zaprimanja,
                    z.tip_dokumenta,
                    z.napomena,
                    z.datum_kreiranja,
                    u.firstname,
                    u.lastname,
                    e.filename as doc_filename,
                    e.filepath as doc_filepath,
                    p.naziv as posiljatelj_full_naziv,
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta,
                    CONCAT(p.klasa_br, '-', p.sadrzaj, '/', p.godina, '-', p.dosje_broj, '/', p.predmet_rbr) as klasa_format,
                    pos.naziv as posiljatelj_registry_naziv
                FROM " . MAIN_DB_PREFIX . "a_zaprimanje z
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON z.fk_user_creat = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files e ON z.fk_ecm_file = e.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet p ON z.ID_predmeta = p.ID_predmeta
                LEFT JOIN " . MAIN_DB_PREFIX . "a_posiljatelji pos ON z.fk_posiljatelj = pos.rowid
                WHERE 1=1";

        if (!empty($filters['godina'])) {
            $sql .= " AND p.godina = '" . $db->escape($filters['godina']) . "'";
        }
        if (!empty($filters['mjesec'])) {
            $sql .= " AND MONTH(z.datum_zaprimanja) = " . (int)$filters['mjesec'];
        }
        if (!empty($filters['nacin'])) {
            $sql .= " AND z.nacin_zaprimanja = '" . $db->escape($filters['nacin']) . "'";
        }
        if (!empty($filters['tip'])) {
            $sql .= " AND z.tip_dokumenta = '" . $db->escape($filters['tip']) . "'";
        }
        if (!empty($filters['search'])) {
            $search = $db->escape($filters['search']);
            $sql .= " AND (z.posiljatelj_naziv LIKE '%" . $search . "%'
                      OR p.naziv_predmeta LIKE '%" . $search . "%'
                      OR e.filename LIKE '%" . $search . "%'
                      OR z.posiljatelj_broj LIKE '%" . $search . "%')";
        }

        $sql .= " ORDER BY z.datum_zaprimanja DESC, z.datum_kreiranja DESC";

        $resql = $db->query($sql);

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $zaprimanja[] = $obj;
            }
        }

        return $zaprimanja;
    }

    public static function getZaprimanjeById($db, $ID_zaprimanja)
    {
        $sql = "SELECT
                    z.*,
                    u.firstname,
                    u.lastname,
                    e.filename as doc_filename,
                    e.filepath as doc_filepath,
                    p.naziv as posiljatelj_full_naziv,
                    p.adresa as posiljatelj_adresa,
                    p.email as posiljatelj_email,
                    p.telefon as posiljatelj_telefon
                FROM " . MAIN_DB_PREFIX . "a_zaprimanje z
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON z.fk_user_creat = u.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files e ON z.fk_ecm_file = e.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_posiljatelji p ON z.fk_posiljatelj = p.rowid
                WHERE z.ID_zaprimanja = " . (int)$ID_zaprimanja;

        $resql = $db->query($sql);

        if ($resql && $db->num_rows($resql) > 0) {
            return $db->fetch_object($resql);
        }

        return null;
    }

    public static function deleteZaprimanje($db, $ID_zaprimanja)
    {
        try {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "a_zaprimanje
                    WHERE ID_zaprimanja = " . (int)$ID_zaprimanja;

            $resql = $db->query($sql);

            if (!$resql) {
                throw new Exception('Database error: ' . $db->lasterror());
            }

            return [
                'success' => true,
                'message' => 'Zaprimanje uspješno obrisano'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function updateZaprimanje(
        $db,
        $ID_zaprimanja,
        $datum_zaprimanja,
        $nacin_zaprimanja,
        $posiljatelj_naziv,
        $tip_dokumenta,
        $fk_posiljatelj = null,
        $posiljatelj_broj = null,
        $fk_akt_za_prilog = null,
        $napomena = null
    ) {
        try {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "a_zaprimanje SET
                        datum_zaprimanja = '" . $db->escape($datum_zaprimanja) . "',
                        nacin_zaprimanja = '" . $db->escape($nacin_zaprimanja) . "',
                        posiljatelj_naziv = '" . $db->escape($posiljatelj_naziv) . "',
                        tip_dokumenta = '" . $db->escape($tip_dokumenta) . "',
                        fk_posiljatelj = " . ($fk_posiljatelj ? (int)$fk_posiljatelj : "NULL") . ",
                        posiljatelj_broj = " . ($posiljatelj_broj ? "'" . $db->escape($posiljatelj_broj) . "'" : "NULL") . ",
                        fk_akt_za_prilog = " . ($fk_akt_za_prilog ? (int)$fk_akt_za_prilog : "NULL") . ",
                        napomena = " . ($napomena ? "'" . $db->escape($napomena) . "'" : "NULL") . "
                    WHERE ID_zaprimanja = " . (int)$ID_zaprimanja;

            $resql = $db->query($sql);

            if (!$resql) {
                throw new Exception('Database error: ' . $db->lasterror());
            }

            return [
                'success' => true,
                'message' => 'Zaprimanje uspješno ažurirano'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function searchPosiljatelji($db, $query)
    {
        $results = [];

        $sql = "SELECT rowid, naziv, adresa, email, telefon, oib
                FROM " . MAIN_DB_PREFIX . "a_posiljatelji
                WHERE naziv LIKE '%" . $db->escape($query) . "%'
                   OR oib LIKE '%" . $db->escape($query) . "%'
                   OR email LIKE '%" . $db->escape($query) . "%'
                ORDER BY naziv ASC
                LIMIT 10";

        $resql = $db->query($sql);

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $results[] = $obj;
            }
        }

        return $results;
    }

    public static function uploadZaprimljenDokument($db, $conf, $file, $ID_predmeta, $datum_zaprimanja)
    {
        require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

        $date = new DateTime($datum_zaprimanja);
        $year = $date->format('Y');
        $month = $date->format('m');

        $relative_path = 'SEUP/Zaprimanja/' . $year . '/' . $month;
        $full_path = DOL_DATA_ROOT . '/ecm/' . $relative_path;

        if (!is_dir($full_path)) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            dol_mkdir($full_path);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $filename = 'zaprimanje_' . date('YmdHis') . '_' . $safe_filename . '.' . $extension;
        $dest_file = $full_path . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
            return false;
        }

        $ecmfile = new EcmFiles($db);
        $ecmfile->filepath = $relative_path;
        $ecmfile->filename = $filename;
        $ecmfile->label = 'Zaprimljeni dokument';
        $ecmfile->fullpath_orig = $file['name'];
        $ecmfile->gen_or_uploaded = 'uploaded';
        $ecmfile->description = 'Zaprimljeni dokument - Predmet ID: ' . $ID_predmeta;
        $ecmfile->entity = $conf->entity;

        $result = $ecmfile->create($GLOBALS['user']);

        if ($result > 0) {
            return $ecmfile->id;
        }

        return false;
    }

    public static function exportExcelFiltered($db, $filters)
    {
        $sql = "SELECT
                    z.ID_zaprimanja,
                    z.datum_zaprimanja,
                    z.nacin_zaprimanja,
                    z.tip_dokumenta,
                    z.posiljatelj_naziv,
                    z.posiljatelj_broj,
                    z.napomena,
                    e.filename as dokument_naziv,
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta,
                    CONCAT(p.klasa_br, '-', p.sadrzaj, '/', p.godina, '-', p.dosje_broj, '/', p.predmet_rbr) as klasa_format,
                    DATE_FORMAT(z.datum_zaprimanja, '%d.%m.%Y') as datum_format
                FROM " . MAIN_DB_PREFIX . "a_zaprimanje z
                LEFT JOIN " . MAIN_DB_PREFIX . "ecm_files e ON z.fk_ecm_file = e.rowid
                LEFT JOIN " . MAIN_DB_PREFIX . "a_predmet p ON z.ID_predmeta = p.ID_predmeta
                WHERE 1=1";

        if (!empty($filters['godina'])) {
            $sql .= " AND p.godina = '" . $db->escape($filters['godina']) . "'";
        }
        if (!empty($filters['mjesec'])) {
            $sql .= " AND MONTH(z.datum_zaprimanja) = " . (int)$filters['mjesec'];
        }
        if (!empty($filters['nacin'])) {
            $sql .= " AND z.nacin_zaprimanja = '" . $db->escape($filters['nacin']) . "'";
        }
        if (!empty($filters['tip'])) {
            $sql .= " AND z.tip_dokumenta = '" . $db->escape($filters['tip']) . "'";
        }
        if (!empty($filters['search'])) {
            $search = $db->escape($filters['search']);
            $sql .= " AND (z.posiljatelj_naziv LIKE '%" . $search . "%'
                      OR p.naziv_predmeta LIKE '%" . $search . "%'
                      OR e.filename LIKE '%" . $search . "%')";
        }

        $sql .= " ORDER BY z.datum_zaprimanja DESC";

        $resql = $db->query($sql);
        $zaprimanja = [];
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $zaprimanja[] = $obj;
            }
        }

        self::generateExcelOutput($zaprimanja, 'zaprimanja_filtered_' . date('Y-m-d'));
    }

    private static function generateExcelOutput($data, $filename)
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
        echo '<xml>';
        echo '<x:ExcelWorkbook>';
        echo '<x:ExcelWorksheets>';
        echo '<x:ExcelWorksheet>';
        echo '<x:Name>Zaprimanja</x:Name>';
        echo '<x:WorksheetOptions>';
        echo '<x:DisplayGridlines/>';
        echo '</x:WorksheetOptions>';
        echo '</x:ExcelWorksheet>';
        echo '</x:ExcelWorksheets>';
        echo '</x:ExcelWorkbook>';
        echo '</xml>';
        echo '</head>';
        echo '<body>';
        echo '<table border="1">';

        echo '<thead>';
        echo '<tr style="background-color: #4a5568; color: white; font-weight: bold;">';
        echo '<th>ID</th>';
        echo '<th>Datum Zaprimanja</th>';
        echo '<th>Klasa Predmeta</th>';
        echo '<th>Naziv Predmeta</th>';
        echo '<th>Pošiljatelj</th>';
        echo '<th>Broj Pošiljatelja</th>';
        echo '<th>Dokument</th>';
        echo '<th>Tip Dokumenta</th>';
        echo '<th>Način Zaprimanja</th>';
        echo '<th>Napomena</th>';
        echo '</tr>';
        echo '</thead>';

        echo '<tbody>';
        foreach ($data as $zaprimanje) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($zaprimanje->ID_zaprimanja) . '</td>';
            echo '<td>' . htmlspecialchars($zaprimanje->datum_format) . '</td>';
            echo '<td>' . htmlspecialchars($zaprimanje->klasa_format ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($zaprimanje->naziv_predmeta ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($zaprimanje->posiljatelj_naziv) . '</td>';
            echo '<td>' . htmlspecialchars($zaprimanje->posiljatelj_broj ?: '—') . '</td>';
            echo '<td>' . htmlspecialchars($zaprimanje->dokument_naziv ?: '—') . '</td>';

            $tipLabels = [
                'novi_akt' => 'Novi akt',
                'prilog_postojecem' => 'Prilog postojećem',
                'nerazvrstan' => 'Nerazvrstan'
            ];
            echo '<td>' . htmlspecialchars($tipLabels[$zaprimanje->tip_dokumenta] ?? $zaprimanje->tip_dokumenta) . '</td>';

            $nacinLabels = [
                'posta' => 'Pošta',
                'email' => 'E-mail',
                'rucno' => 'Na ruke',
                'courier' => 'Kurirska služba'
            ];
            echo '<td>' . htmlspecialchars($nacinLabels[$zaprimanje->nacin_zaprimanja] ?? $zaprimanje->nacin_zaprimanja) . '</td>';
            echo '<td>' . htmlspecialchars($zaprimanje->napomena ?: '—') . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';

        echo '</table>';
        echo '</body>';
        echo '</html>';

        exit;
    }
}
