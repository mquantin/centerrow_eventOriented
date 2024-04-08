<?php 
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class OnePropValues extends AbstractHelper
{   
    public function __invoke($propertyData, $labelInfo, $showLocale, $filterLocale = False, $lang=Null, $showValueAnnotations = False ) 
    {
        $filterLocaleCallback = function ($value) use ($lang) {
            $valueLang = $value->lang();
            return $valueLang == '' || strcasecmp($valueLang, $lang) === 0;
        };

        $view = $this->getView();
        $escape = $view->plugin('escapeHtml');
        $translate = $view->plugin('translate');
        $property = $propertyData['property'];
        $propertyValues = $propertyData['values'];
        $propertyLabel = $propertyData['alternate_label'] ?: $translate($property->label());
        if ($filterLocale) {
            $propertyValues = array_filter($propertyValues, $filterLocaleCallback);
        };
        $html = "<div class='property' id = {$property->term()}>";
        $html .= "<dt> {$propertyLabel}";
            if ('term' === $labelInfo) {
                $displayLabelInfo = $property->term();
            } elseif ('vocab' === $labelInfo) {
                $displayLabelInfo = $property->vocabulary()->label();
            } else {
                $displayLabelInfo = null;
            };
            if ($displayLabelInfo) {
                $html .= "<span class='field-term'>({$displayLabelInfo})</span>";
            }
        $html .= "</dt>";
        foreach ($propertyValues as $value){
            $valueType = $value->type();
            $valueLang = $value->lang();
            $valueAnnotation = $value->valueAnnotation();
            $classValue = ['value'];
            if ('litteral' == $valueType) {
                $classValue[] = 'litteral';
            } elseif ('resource' == $valueType || strpos($valueType, 'resource') !== false) {
                $classValue[] = 'resource';
                $classValue[] = $value->valueResource()->resourceName();
            } elseif ('uri' == $valueType) {
                $classValue[] = 'uri';
            };
            $classValues = implode(' ', $classValue);
            $html .= "<dd class='{$classValues}' lang='{$valueLang}'>";
            if ($showLocale && $valueLang){
                $html .= "<div class='language'>{$valueLang}</div>";
            }
            $content = $filterLocale ? $value->asHtml($lang) : $value->asHtml();
            $html .= "<div class='value-content'>{$content}</div>";
            if(!$value->isPublic()){
                $html .= "<div class='o-icon-private' aria-role='tooltip' title='Private' aria-label='Private'></div>";
            }
            if ($valueAnnotation && $showValueAnnotations){
                $status = ('expanded' === $showValueAnnotations) ? 'collapse' : 'expand';
                $html .= "<a href='#' class='{$status}' aria-label='{$status}'>";
                    $html .= "<div class='has-annotation o-icon-annotation' aria-role='tooltip' title='Has annotation' aria-label='Has annotation'></div>";
                $html .= "</a>";
                $html .= "<div class='collapsible annotation'>{$valueAnnotation->displayValues()}</div>";
            };
            $html .= "</dd>";
        };
        $html .= "</div>";
        return $html;
    }
}
?>