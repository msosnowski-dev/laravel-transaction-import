<?php

namespace App\Services\Parsers;

use App\Enums\FileType;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class FileParserFactory
{
    public function __construct(
        protected CsvFileParser $csvParser,
        protected JsonFileParser $jsonParser,
        protected XmlFileParser $xmlParser
    ) {}

    /**
     * Resolve parser by FileType enum.
     */
    public function make(FileType $type): FileParserInterface
    {
        return match ($type) {
            FileType::CSV => $this->csvParser,
            FileType::JSON => $this->jsonParser,
            FileType::XML => $this->xmlParser,
        };
    }

    /**
     * Resolve parser from UploadedFile or file path.
     */
    public function makeFromUploadedFile(UploadedFile $file): FileParserInterface
    {
        $extension = $file->getClientOriginalExtension();
        $mime = $file->getClientMimeType();

        $type = FileType::fromExtensionOrMime($extension, $mime);

        if($type === null) {
            throw new InvalidArgumentException("Unsupported file type: {$mime} ({$extension}). Supported formats: CSV, JSON, XML.");
        }

        return $this->make($type);
    }

}