<?php

declare(strict_types=1);

namespace Phlib\Mail\Mime;

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

    /**
     * Add additional content type parameters to the base value
     *
     * @param string $contentType
     * @return string
     */
    protected function addContentTypeParameters(string $contentType): string
    {
        if ($this->reportType) {
            $contentType .= "; report-type={$this->reportType}";
        }

        return parent::addContentTypeParameters($contentType);
    }
}
