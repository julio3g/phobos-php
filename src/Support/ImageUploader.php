<?php

declare(strict_types=1);

namespace Phobos\Support;

use DomainException;

/**
 * Upload seguro de imagem: valida tamanho, confere o conteúdo real com
 * getimagesize (não confia no mime enviado) e grava com nome aleatório e
 * extensão derivada do tipo detectado.
 */
final class ImageUploader
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];

    public function __construct(private readonly string $directory, private readonly string $publicPrefix = '/uploads')
    {
    }

    /**
     * @param array<string,mixed>|null $file entrada de $_FILES
     * @return string|null URL pública, ou null se nenhum arquivo foi enviado
     */
    public function store(?array $file, string $folder): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new DomainException('Falha ao enviar a imagem.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new DomainException('A imagem deve ter no máximo 5MB.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new DomainException('Arquivo inválido.');
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !isset(self::TYPES[$info[2]])) {
            throw new DomainException('Tipo de arquivo não permitido. Use JPG, PNG, WebP ou GIF.');
        }

        $dir = rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new DomainException('Não foi possível criar o diretório de upload.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$info[2]];
        if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) {
            throw new DomainException('Não foi possível salvar a imagem.');
        }

        return "{$this->publicPrefix}/$folder/$name";
    }
}
