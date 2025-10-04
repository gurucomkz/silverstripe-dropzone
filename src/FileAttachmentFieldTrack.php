<?php

namespace UncleCheese\Dropzone;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Track files as they're uploaded and remove when they've been saved.
 * 
 * @property string $ControllerClass
 * @property int $RecordID
 * @property string $RecordClass
 * 
 * @property File $File
 * @property int $FileID
 * 
 * @package unclecheese/silverstripe-dropzone
 */
class FileAttachmentFieldTrack extends DataObject
{
    private static $db = array(
        'ControllerClass' => 'Varchar(60)',
        'RecordID' => 'Int',
        'RecordClass' => 'Varchar(60)',
    );

    private static $has_one = array(
        'File' => File::class,
    );

    private static $table_name = 'FileAttachmentFieldTrack';

    public static function untrack($fileIDs)
    {
        if (!$fileIDs) {
            return;
        }
        $fileIDs = (array)$fileIDs;
        $trackRecords = FileAttachmentFieldTrack::get()->filter(array('FileID' => $fileIDs));
        foreach ($trackRecords as $trackRecord) {
            $trackRecord->delete();
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->exists()) {
            // Store record this file was tracked on.
            $controller = Controller::curr();
            if (!$this->RecordID && $controller) {
                $pageRecord = null;
                if (method_exists($controller, 'data')) {
                    // Store page visiting on frontend (ContentController)
                    $pageRecord = $controller->data();
                } elseif ($controller instanceof LeftAndMain) {
                    // Store editing page in CMS (LeftAndMain)
                    $id = $controller->currentRecordID();
                    $pageRecord = $controller->getRecord($id);
                } elseif ($controller->hasMethod('getRecord')) {
                    $pageRecord = $controller->getRecord(); // @phpstan-ignore method.notFound
                }

                if ($pageRecord && $pageRecord instanceof DataObjectInterface) {
                    $this->RecordID = $pageRecord->ID; // @phpstan-ignore property.notFound
                    $this->RecordClass = $pageRecord->ClassName; // @phpstan-ignore property.notFound
                }
            }
        }
    }

    public function setRecord($record)
    {
        $this->RecordID = $record->ID;
        $this->RecordClass = $record->ClassName;
    }

    public function Record()
    {
        if ($this->RecordClass && $this->RecordID) {
            return DataObject::get_one($this->RecordClass, "ID = " . (int)$this->RecordID);
        }
    }
}
