<?php 
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class SortLinkedResources extends AbstractHelper
{
    public function __invoke($resource = null) 
    { 
      $api = $services->get('Omeka\ApiManager');
      //les classes sont regroupées par catégories puisque omeka-s ne propose pas de gérer l'héritage de classes
      //j'ai choisi de réunir par classes plus que par propriété car par exemple la classe "acquisition" pourra être montré sur une fiche E22HMO et une fiche E21Person; 
      // a choper depuis les settings du theme
      $displayedEventsMapping = array(
        'Production' => array('crm:E12_Production',),
        'Modification' => array('crm:E11_Modification', 'crm:E79_Part_Addition', 'crm:E80_Part_Removal'),
        'Acquisition' => array('crm:E8_Acquisition', 'crm:E96_Purchase', 'crm:E10_Transfer_of_Custody'),
      );
      //mapping of class id with categories
      $eventClassId2categ = array();
      foreach($displayedEventsMapping as $eventCategory=>$eventClassTermArray){
          foreach($eventClassTermArray as $eventClassTerm){
              $eventClassId = $this->api()->searchOne('resource_classes', ['term'=>$eventClassTerm])->getContent()->id();
              $eventClassId2categ[$eventClassId] = $eventCategory;
          }
      };
      
      
      $skippedPropsList = array('rdfs:seeAlso', );
      $skippedPropsIdList = array();
      foreach($skippedPropsList as $skipedPropTerm){
          $skippedPropId = $api->searchOne('properties', ['term'=>$skipedPropTerm])->getContent()->id();
          $skippedPropsIdList[] = $skippedPropId;
      };
      
      $foundEvents = array();
      $otherLinkedItems = array();

      //this finds the linked resources and order them by date (time_span)
      $i=0;
      $j=0;
      $maxTerm = 20;
      $max_itemPerTerm = 8;
      //todo: fetch third degree items for object <- (production -> designOrProcedure) <- creation  
      foreach ($resource->subjectValues() as $index => $subjectValues) {
          if(++$i > $maxTerm) break;//max terms analyzed
          $termID = explode('-',$index)[0];
          if (!in_array($termID, $skippedPropsIdList)){//the prop is not among the forbidden ones
              foreach ($subjectValues as $subjectValue) {
                  if(++$j > $max_itemPerTerm) break;//max item analyzed per term
                  // $this->logger()->info(json_encode($subjectValue['val']->resource()));
                  $subjectResource = $subjectValue['val']->resource();
                  if ($subjectResource->getControllerName($subjectResource) === 'item'){//if the linked item is not a media (through RightsHolder property)
                      $subjectResourceClassId = $subjectResource->resourceClass()->id();
                      $datelit = $subjectResource->value('crm:P4_has_time-span', ['literal']);
                      preg_match('/\d\d\d\d?/', $datelit, $date);
                      $dateindex = 100*$date[0];// ex: 194400 for 1944
                      if (in_array($subjectResourceClassId, array_keys($eventClassId2categ))){
                          while (key_exists($dateindex, $foundEvents)) {++$dateindex; };//if key allready in use (same date); then go to next index (194401 for example)
                          $foundEvents[$dateindex] = $subjectResource;
                      } else {
                          while (key_exists($dateindex, $otherLinkedItems)) {++$dateindex; };
                          $otherLinkedItems[$dateindex] = $subjectResource;
                      };
                  }
                  
              };
          }  
      };
      ksort($foundEvents);
      ksort($otherLinkedItems);

      return [$foundEvents, $otherLinkedItems];
    }
}
