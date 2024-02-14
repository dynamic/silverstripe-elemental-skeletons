<?php

namespace DNADesign\ElementalSkeletons\Models;

use Sheadawson\DependentDropdown\Forms\DependentDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;

/**
 * Creates a archetype of elements that can be used as a template that is defined
 * within the CMS
 */
class SkeletonPart extends DataObject {

	private static $db = array(
		'ElementType' => 'Varchar',
        'Style' => 'Varchar',
		'Sort' => 'Int',
	);

	private static $has_one = array(
		'Skeleton' => Skeleton::class
	);

	private static $table_name = 'ElementSkeletonPart';

	private static $summary_fields = array(
		'ElementName'
	);

	private static $field_labels = array(
		'ElementName' => 'Element Name'
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

        $fields->removeByName('Style');

		$pageType = $this->Skeleton()->PageType;
		$elementTypes = $pageType::singleton()->getElementalTypes();

		$fields->removeByName('Sort');
		$fields->removeByName('SkeletonID');
		$fields->replaceField('ElementType', $et = DropdownField::create('ElementType', 'Which element type', $elementTypes));
		$et->setEmptyString('Please choose...');

        $styleOptions = function($elementType) {
            return $elementType::singleton()->config()->get('styles');
        };

        $styles = DependentDropdownField::create('Style', 'Style', $styleOptions)->setDepends($et);
        $fields->insertAfter('ElementType', $styles);

		return $fields;
	}

	public function ElementName() {
		return singleton($this->ElementType)->getType();
	}
}
