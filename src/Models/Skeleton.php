<?php

namespace DNADesign\ElementalSkeletons\Models;

use DNADesign\Elemental\Extensions\ElementalAreasExtension;

use LeKoala\CmsActions\CustomAction;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

use Page;

/**
 * Creates a Skeleton of elements that can be used to setup a page
 */
class Skeleton extends DataObject {

	private static $db = array(
		'Title' => 'Varchar',
		'PageType' => 'Varchar'
	);

	private static $has_many = array(
		'Parts' => SkeletonPart::class
	);

	private static $table_name = 'ElementSkeletons';

	private static $summary_fields = array(
		'Title',
		'PageTypeName'
	);

	private static $field_labels = array(
		'PageTypeName' => 'Page Type'
	);

	public static function getDecoratedBy($extension, $baseClass){
		$classes = array();

		foreach(ClassInfo::subClassesFor($baseClass) as $className) {
            $class = $className::singleton();
			if ($class::has_extension($className, $extension)){
				$classes[$className] = singleton($className)->singular_name();
			}
		}
		return $classes;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$pageTypes = self::getDecoratedBy(ElementalAreasExtension::class, Page::class);
		$fields->removeByName('Sort');
		$fields->replaceField('PageType', $pt = DropdownField::create('PageType', 'Which page type to use as the base', $pageTypes));
		$pt->setEmptyString('Please choose...');
		$pt->setRightTitle('This will determine which elements are possible to add to the skeleton');
		if ($this->isinDB()) {
			$gf = $fields->fieldByName('Root.Parts.Parts');
			$gfc = $gf->getConfig();
			$gfc->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
			$gfc->addComponent(new GridFieldOrderableRows('Sort'));
			$fields->removeByName('Parts');
			$fields->addFieldToTab('Root.Main', $gf);

            $fields->push(TreeDropdownField::create('ParentID', 'Parent Page', Page::class)->setEmptyString('Parent page (empty for root)'));
		}

		return $fields;
	}

    /**
     * @return FieldList
     */
    public function getCMSActions(): FieldList
    {
        $actions = parent::getCMSActions();

        if ($this->isinDB()) {
            $actions->push(
                CustomAction::create('createPage', 'Create new ' . $this->Title . ' page')
                    ->addExtraClass('btn btn-success font-icon-plus-circled')
            );
        }

        return $actions;
    }

    public function PageTypeName() {
		return singleton($this->PageType)->singular_name();
	}

    /**
     * @param $request
     * @return string
     */
    public function createPage($request): string
    {
        $pageType = $this->PageType;
        $parentID = $request['ParentID'] ?? 0;

        $page = $pageType::create();
        $page->ParentID = $parentID;
        $page->write();
        $page->writeToStage(Versioned::DRAFT);

        $area = $page->ElementalArea();

        foreach($this->Parts() as $part) {
            $type = $part->ElementType;
            $element = $type::create();
            $element->Style = $part->Style;
            $element->write();
            $element->writeToStage(Versioned::DRAFT);
            $area->Elements()->add($element);
        }

        return 'Page Added';
    }
}
