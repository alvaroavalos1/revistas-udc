<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function url_asset(?string $path): string {
    if (!$path) return '';
    if (str_starts_with($path, 'http') || str_starts_with($path, 'data:')) return $path;
    return '../' . $path;
}

function _r2_client(): ?\Aws\S3\S3Client {
    if (!class_exists('Aws\S3\S3Client')) return null;
    static $client = null;
    if (!$client) {
        $client = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => 'auto',
            'endpoint'    => 'https://' . getenv('R2_ACCOUNT_ID') . '.r2.cloudflarestorage.com',
            'credentials' => [
                'key'    => getenv('R2_ACCESS_KEY'),
                'secret' => getenv('R2_SECRET_KEY'),
            ],
        ]);
    }
    return $client;
}

function upload_to_r2(string $tmp_path, string $key, string $mime): string|false {
    $s3 = _r2_client();
    if (!$s3) return false;
    try {
        $s3->putObject([
            'Bucket'      => getenv('R2_BUCKET'),
            'Key'         => $key,
            'SourceFile'  => $tmp_path,
            'ContentType' => $mime,
        ]);
        return rtrim(getenv('R2_PUBLIC_URL'), '/') . '/' . $key;
    } catch (\Exception $e) {
        return false;
    }
}

function delete_from_r2(?string $url): void {
    if (!$url || !str_starts_with($url, 'http')) return;
    $s3 = _r2_client();
    if (!$s3) return;
    $base = rtrim(getenv('R2_PUBLIC_URL'), '/') . '/';
    $key  = str_starts_with($url, $base)
        ? substr($url, strlen($base))
        : ltrim(parse_url($url, PHP_URL_PATH), '/');
    try {
        $s3->deleteObject(['Bucket' => getenv('R2_BUCKET'), 'Key' => $key]);
    } catch (\Exception $e) {}
}
