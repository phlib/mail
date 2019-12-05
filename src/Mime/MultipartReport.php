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

    /**
     * Set report type
     *
     * @param string $reportType
     * @return $this
     */
    public function setReportType(string $reportType): self
    {
        $this->reportType = $reportType;

        return $this;
    }

    /**
     * Get report type
     *
     * @return string
     */
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
