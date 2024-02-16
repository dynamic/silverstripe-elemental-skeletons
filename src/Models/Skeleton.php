<?php

namespace DNADesign\ElementalSkeletons\Models;

use DNADesign\Elemental\Extensions\ElementalAreasExtension;
use DNADesign\Elemental\Models\ElementalArea;
use LeKoala\CmsActions\CustomAction;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Creates a Skeleton of elements that can be used to set up a page
 *
 * @property string $Title
 * @property string $PageType
 * @property int $ElementsID
 * @method \DNADesign\Elemental\Models\ElementalArea Elements()
 * @mixin \DNADesign\Elemental\Extensions\ElementalAreasExtension
 */
class Skeleton extends DataObject
{
    /**
     * @var array|string[]
     */
    private static array $db = [
        'Title' => 'Varchar',
        'PageType' => 'Varchar',
    ];

    /**
     * @var string
     */
    private static string $table_name = 'ElementSkeletons';

    /**
     * @var array|string[]
     */
    private static array $has_one = [
        'Elements' => ElementalArea::class,
    ];

    /**
     * @var array|string[]
     */
    private static array $owns = [
        'Elements',
    ];

    /**
     * @var array|string[]
     */
    private static array $cascade_deletes = [
        'Elements',
    ];

    /**
     * @var array|string[]
     */
    private static array $cascade_duplicates = [
        'Elements',
    ];

    /**
     * @var array|string[]
     */
    private static array $extensions = [
        ElementalAreasExtension::class,
    ];

    /**
     * @var array|string[]
     */
    private static array $summary_fields = [
        'Title',
        'PageTypeName',
    ];

    /**
     * @var array|string[]
     */
    private static array $field_labels = [
        'PageTypeName' => 'Page Type',
    ];

    /**
     * @param $extension
     * @param $baseClass
     * @return array
     * @throws \ReflectionException
     */
    public static function getDecoratedBy($extension, $baseClass): array
    {
        $classes = [];

        foreach (ClassInfo::subClassesFor($baseClass) as $className) {
            $class = $className::singleton();
            if ($class::has_extension($className, $extension)) {
                $classes[$className] = singleton($className)->singular_name();
            }
        }
        return $classes;
    }

    /**
     * @return FieldList
     * @throws \ReflectionException
     */
    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $pageTypes = self::getDecoratedBy(ElementalAreasExtension::class, \Page::class);

        $fields->removeByName('Sort');
        $fields->replaceField('PageType', $pt = DropdownField::create('PageType', 'Which page type to use as the base', $pageTypes));

        $pt->setEmptyString('Please choose...');
        $pt->setRightTitle('This will determine which elements are possible to add to the skeleton');

        if ($this->isinDB()) {
            $fields->push(TreeDropdownField::create('ParentID', 'Parent Page', \Page::class)->setEmptyString('Parent page (empty for root)'));
            $fields->push(TextField::create('PageTitle', 'Page Title')->setDescription('Title for new page'));
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

    /**
     * @return string
     */
    public function PageTypeName(): string
    {
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
        if ($request['PageTitle']) {
            $page->Title = $request['PageTitle'];
        }
        $page->write();
        $page->writeToStage(Versioned::DRAFT);

        $area = $page->ElementalArea();

        foreach ($this->Elements()->Elements() as $element) {
            $copy = $element->duplicate();
            $copy->write();
            $copy->writeToStage(Versioned::DRAFT);
            $area->Elements()->add($copy);
        }

        return 'Page Added';
    }

    /**
     * @return string
     */
    public function CMSEditLink(): string
    {
        return Controller::join_links(
            'admin',
            'elemental-skeletons',
            'DNADesign-ElementalSkeletons-Models-Skeleton',
            'EditForm',
            'field',
            'DNADesign-ElementalSkeletons-Models-Skeleton',
            'item',
            $this->ID,
            'edit'
        );
    }

    /**
     * Retrieve a elemental area relation name which this element owns
     *
     * @return string
     */
    public function getOwnedAreaRelationName(): string
    {
        $has_one = $this->config()->get('has_one');

        foreach ($has_one as $relationName => $relationClass) {
            if ($relationClass === ElementalArea::class && $relationName !== 'Parent') {
                return $relationName;
            }
        }

        return 'Elements';
    }
}
