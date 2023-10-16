<?php

declare(strict_types=1);

namespace Phlib\Mail\Mime;

use Symfony\Component\Mime\Header\ParameterizedHeader;

class MultipartReport extends AbstractMime
{
    /**
     * @var string
     */
    protected $type = 'multipart/report';

    /**
     * @var string
     */
    private $reportType;

    public function setReportType(string $reportType): self
    {
        $this->reportType = $reportType;

        return $this;
    }

    public function getReportType(): ?string
    {
        return $this->reportType;
    }

    protected function addContentTypeParameters(ParameterizedHeader $contentTypeHeader): void
    {
        if ($this->reportType) {
            $contentTypeHeader->setParameter('report-type', $this->reportType);
        }

        parent::addContentTypeParameters($contentTypeHeader);
    }
}
