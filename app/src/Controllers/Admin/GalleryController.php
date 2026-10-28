<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Db;
use App\Services\Uploads;
use App\Shop;

/** Galerija radova: kupac može da izabere tortu po uzoru na sliku (šifra, npr. D-123). */
final class GalleryController extends BaseController
{
    public function index(array $p): void
    {
        $images = Db::all('SELECT * FROM gallery_images WHERE shop_id = ? ORDER BY sort, id DESC', [Shop::id()]);
        $this->render('gallery', ['images' => $images], 'galerija');
    }

    public function upload(array $p): void
    {
        $files = $_FILES['images'] ?? null;
        if (!$files || !is_array($files['name'])) {
            redirect('/admin/galerija');
        }
        $prefix = strtoupper(input('prefix', 'T'));
        $next = (int)Db::value('SELECT COUNT(*) FROM gallery_images WHERE shop_id = ?', [Shop::id()]) + 1;
        $ok = 0;
        $errors = [];
        foreach ($files['name'] as $i => $name) {
            if ($name === '') {
                continue;
            }
            $file = [
                'name' => $name, 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i],
            ];
            try {
                $path = Uploads::saveImage($file, Shop::id(), 'gallery', 1400);
                Db::insert('gallery_images', [
                    'shop_id' => Shop::id(),
                    'code' => $prefix !== '' ? sprintf('%s-%03d', $prefix, $next++) : '',
                    'title' => '',
                    'image_path' => $path,
                    'created_at' => now(),
                ]);
                $ok++;
            } catch (\RuntimeException $e) {
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }
        if ($ok) {
            flash('success', "Dodato slika: $ok.");
        }
        foreach ($errors as $error) {
            flash('error', $error);
        }
        redirect('/admin/galerija');
    }

    public function update(array $p): void
    {
        Db::update('gallery_images', [
            'code' => mb_substr(input('code'), 0, 30),
            'title' => mb_substr(input('title'), 0, 190),
            'sort' => input_int('sort'),
            'active' => isset($_POST['active']) ? 1 : 0,
        ], ['id' => (int)$p['id'], 'shop_id' => Shop::id()]);
        flash('success', 'Sačuvano.');
        redirect('/admin/galerija');
    }

    public function delete(array $p): void
    {
        $row = Db::one('SELECT * FROM gallery_images WHERE id = ? AND shop_id = ?', [(int)$p['id'], Shop::id()]);
        if ($row) {
            Uploads::delete($row['image_path']);
            Db::run('DELETE FROM gallery_images WHERE id = ?', [$row['id']]);
            flash('success', 'Slika je obrisana.');
        }
        redirect('/admin/galerija');
    }
}
