<?php
declare(strict_types=1);

function is_image_content_type(string $contentType): bool
{
    return starts_with(strtolower(trim($contentType)), 'image/');
}

function get_image_info_from_binary(string $binary): array
{
    $info = @getimagesizefromstring($binary);
    if (!is_array($info) || empty($info[0]) || empty($info[1])) {
        return [];
    }

    return [
        'width' => (int)$info[0],
        'height' => (int)$info[1],
        'mime' => strtolower((string)($info['mime'] ?? '')),
    ];
}

function constrain_image_dimensions(int $width, int $height, int $maxSide): array
{
    if ($width <= 0 || $height <= 0) {
        return [0, 0];
    }

    $scale = min(1.0, $maxSide / max($width, $height));
    return [
        max(1, (int)round($width * $scale)),
        max(1, (int)round($height * $scale)),
    ];
}

function encode_image_as_jpeg(string $binary, int $targetWidth, int $targetHeight, int $quality): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }

    $source = @imagecreatefromstring($binary);
    if ($source === false) {
        return null;
    }

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    if ($sourceWidth <= 0 || $sourceHeight <= 0) {
        imagedestroy($source);
        return null;
    }

    $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
    if ($canvas === false) {
        imagedestroy($source);
        return null;
    }

    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);

    $resampled = imagecopyresampled(
        $canvas,
        $source,
        0,
        0,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $sourceWidth,
        $sourceHeight
    );
    imagedestroy($source);

    if ($resampled === false) {
        imagedestroy($canvas);
        return null;
    }

    ob_start();
    $ok = imagejpeg($canvas, null, max(1, min(100, $quality)));
    $output = ob_get_clean();
    imagedestroy($canvas);

    if (!$ok || !is_string($output) || $output === '') {
        return null;
    }

    return $output;
}

function apply_4000px_limit(string $binary, int $sourceWidth, int $sourceHeight, int $sourceBytes): ?array
{
    if ($sourceWidth <= 4000 && $sourceHeight <= 4000) {
        return null;
    }

    $baseDimensions = constrain_image_dimensions($sourceWidth, $sourceHeight, 4000);
    $qualityCandidates = [92, 88, 84, 80, 76, 72, 68, 64];

    foreach ($qualityCandidates as $quality) {
        $encoded = encode_image_as_jpeg($binary, $baseDimensions[0], $baseDimensions[1], $quality);
        if (!is_string($encoded) || $encoded === '') {
            continue;
        }

        return [
            'body' => $encoded,
            'contentType' => 'image/jpeg',
            'sourceBytes' => $sourceBytes,
            'outputBytes' => strlen($encoded),
            'sourceWidth' => $sourceWidth,
            'sourceHeight' => $sourceHeight,
            'outputWidth' => $baseDimensions[0],
            'outputHeight' => $baseDimensions[1],
            'quality' => $quality,
        ];
    }

    return null;
}

function compress_to_2mb_jpeg(string $binary, int $sourceWidth, int $sourceHeight, int $sourceBytes): ?array
{
    if ($sourceBytes <= 2000000) {
        return null;
    }

    $baseDimensions = [$sourceWidth, $sourceHeight];
    $qualityCandidates = [92, 88, 84, 80, 76, 72, 68, 64, 60, 56, 52, 48, 44, 40, 36, 32, 28, 24];
    $scale = 1.0;
    $minimumScale = 0.25;
    $scaleStep = 0.88;

    $best = null;
    while ($scale >= $minimumScale) {
        $targetWidth = max(1, (int)round($baseDimensions[0] * $scale));
        $targetHeight = max(1, (int)round($baseDimensions[1] * $scale));

        foreach ($qualityCandidates as $quality) {
            $encoded = encode_image_as_jpeg($binary, $targetWidth, $targetHeight, $quality);
            if (!is_string($encoded) || $encoded === '') {
                continue;
            }

            $encodedBytes = strlen($encoded);
            $candidate = [
                'body' => $encoded,
                'contentType' => 'image/jpeg',
                'sourceBytes' => $sourceBytes,
                'outputBytes' => $encodedBytes,
                'sourceWidth' => $sourceWidth,
                'sourceHeight' => $sourceHeight,
                'outputWidth' => $targetWidth,
                'outputHeight' => $targetHeight,
                'quality' => $quality,
            ];

            if ($best === null || $candidate['outputBytes'] < $best['outputBytes']) {
                $best = $candidate;
            }

            if ($encodedBytes <= 2000000) {
                return $candidate;
            }
        }

        $scale *= $scaleStep;
    }

    return $best;
}

function process_upload_blob_image(string $binary, string $contentType): ?array
{
    if (!is_image_content_type($contentType)) {
        return null;
    }

    $info = get_image_info_from_binary($binary);
    if (empty($info)) {
        return null;
    }

    $originalBytes = strlen($binary);
    $originalWidth = (int)$info['width'];
    $originalHeight = (int)$info['height'];

    if ($originalWidth <= 4000 && $originalHeight <= 4000 && $originalBytes <= 2000000) {
        return null;
    }

    $stage1 = apply_4000px_limit($binary, $originalWidth, $originalHeight, $originalBytes);
    $workingBinary = is_array($stage1) ? (string)$stage1['body'] : $binary;
    $workingInfo = is_array($stage1)
        ? ['width' => (int)$stage1['outputWidth'], 'height' => (int)$stage1['outputHeight']]
        : ['width' => $originalWidth, 'height' => $originalHeight];

    $stage2 = compress_to_2mb_jpeg(
        $workingBinary,
        (int)$workingInfo['width'],
        (int)$workingInfo['height'],
        strlen($workingBinary)
    );

    if (is_array($stage2)) {
        $stage2['sourceBytes'] = $originalBytes;
        $stage2['sourceWidth'] = $originalWidth;
        $stage2['sourceHeight'] = $originalHeight;
        return $stage2;
    }

    if (is_array($stage1)) {
        return $stage1;
    }

    return null;
}