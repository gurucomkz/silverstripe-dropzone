<?php

namespace UncleCheese\Dropzone;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

/**
 * Adds helper methods to the core {@link File} object
 *
 * @package unclecheese/dropzone
 * @author  Uncle Cheese <unclecheese@leftandmain.com>
 * @extends Extension<File>
 */
class DropzoneFile extends Extension
{


    /**
     * Helper method for determining if this is an Image
     *
     * @return boolean
     */
    public function IsImage()
    {
        return $this->owner instanceof Image;
    }


    /**
     * Gets a thumbnail for this file given a size. If it's an Image,
     * it will render the actual file. If not, it will provide an icon based
     * on the extension.
     *
     * @param  int $w The width of the image
     * @param  int $h The height of the image
     * @return AssetContainer
     */
    public function getPreviewThumbnail($w = null, $h = null)
    {
        if (!$w) {
            $w = $this->owner->config()->get('grid_thumbnail_width');
        }
        if (!$h) {
            $h = $this->owner->config()->get('grid_thumbnail_height');
        }

        if ($this->IsImage() && Director::fileExists($this->owner->Filename)) {
            return $this->owner->FillMax($w, $h);
        }

        $sizes = Config::forClass(FileAttachmentField::class)->get('icon_sizes');
        sort($sizes);

        foreach ($sizes as $size) {
            if ($w <= $size) {
                if ($this->owner instanceof Folder) {
                    $file = $this->getFilenameForType('_folder', $size);
                } else {
                    $file = $this->getFilenameForType($this->owner->getExtension(), $size);
                }
                if (!file_exists(BASE_PATH . '/' . $file)) {
                    $file = $this->getFilenameForType('_blank', $size);
                }

                $image = Image::create();
                $image->setFromLocalFile(Director::getAbsFile($file), basename($file ?? ''));

                return $image;
            }
        }
        return Image::create();
    }


    /**
     * Gets a filename based on the extension and the size
     *
     * @param  string $ext  The extension of the file, e.g. "pdf"
     * @param  int    $size The size of the image
     * @return string
     */
    protected function getFilenameForType($ext, $size)
    {
        return ModuleResourceLoader::singleton()->resolveURL(sprintf(
            'unclecheese/dropzone:images/file-icons/%spx/%s.png',
            $size,
            strtolower($ext ?? '')
        ));
    }
}
