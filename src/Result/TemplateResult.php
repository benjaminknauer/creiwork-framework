<?php

namespace Creios\Creiwork\Framework\Result;

use Creios\Creiwork\Framework\Result\Util\DataResult;

/**
 * Class TemplateResult
 * @package Creios\Creiwork\Util\Results
 */
class TemplateResult extends DataResult
{

    /**
     * @var string
     */
    protected $template;

    /**
     * TemplateResult constructor.
     * @param string $template
     * @param array $data
     */
    public function __construct($template, array $data = null)
    {
        $this->template = $template;
        parent::__construct($data);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

}