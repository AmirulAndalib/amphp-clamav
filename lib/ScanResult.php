<?php declare(strict_types=1);

namespace Amp\ClamAV;

class ScanResult
{
    public function __construct(
        /**
         * The infected file's name.
         *
         * @var string
         */
        public string $filename,

        /**
         * Whether the file is infected or not.
         *
         * @var bool
         */
        public bool $isInfected,

        /**
         * The malware's type.
         *
         * @var string|null
         */
        public ?string $malwareType
    ) {
    }

    /** @codeCoverageIgnore */
    public function __toString()
    {
        return 'ScanResult(filename: ' . \var_export($this->filename, true) . ', isInfected: ' . \var_export($this->isInfected, true) . ', malwareType: ' . \var_export($this->malwareType, true) . ')';
    }

    public function equals(ScanResult $other): bool
    {
        return $this->isInfected == $other->isInfected &&
            $this->filename == $other->filename &&
            $this->malwareType == $other->malwareType;
    }
}
