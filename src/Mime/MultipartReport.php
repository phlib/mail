<?php

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
    public function setReportType($reportType)
    {
        $this->reportType = $reportType;

        return $this;
    }

    /**
     * Get report type
     *
     * @return string
     */
    public function getReportType()
    {
        return $this->reportType;
    }

    /**
     * Add additional content type parameters to the base value
     *
     * @param string $contentType
     * @return string
     */
    protected function addContentTypeParameters($contentType)
    {
        if ($this->reportType) {
            $contentType .= "; report-type={$this->reportType}";
        }

        return parent::addContentTypeParameters($contentType);
    }
}
