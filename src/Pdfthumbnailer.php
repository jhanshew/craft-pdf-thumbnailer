<?php
namespace kraftwerkdesign\craftpdfthumbnailer;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Fields;
use craft\services\Assets;
use craft\events\AssetEvent;
use yii\base\Event;
use Imagick;

class PdfThumbnailer extends Plugin
{
    public static $plugin;
    public $hasCpSettings = true;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register event listeners
        Event::on(
            Assets::class,
            Assets::EVENT_AFTER_SAVE_ASSET,
            function(AssetEvent $event) {
                if ($event->asset->kind === 'pdf') {
                    $this->generateThumbnail($event->asset);
                }
            }
        );
    }

    protected function createSettingsModel(): \craft\base\Model
    {
        return new Settings();
    }

    public function getSettingsResponse()
    {
        return Craft::$app->getView()->renderTemplate(
            'pdf-thumbnailer/settings',
            ['settings' => $this->getSettings()]
        );
    }

    private function generateThumbnail($asset)
    {
        try {
            // Get the PDF file path
            $pdfPath = $asset->getCopyOfFile();
            
            // Create Imagick instance
            $imagick = new Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImage($pdfPath . '[0]'); // Read first page
            $imagick->setImageFormat('jpg');
            
            // Resize to thumbnail size
            $imagick->resizeImage(200, 200, Imagick::FILTER_LANCZOS, 1, true);
            
            // Get settings
            $settings = $this->getSettings();
            $targetFolderId = $settings->thumbnailFolderId;
            
            // Generate unique filename
            $filename = 'thumb_' . $asset->id . '.jpg';
            
            // Get the target folder
            $targetFolder = Craft::$app->assets->getFolderById($targetFolderId);
            
            // Save the thumbnail
            $tempPath = Craft::$app->path->getTempPath() . '/' . $filename;
            $imagick->writeImage($tempPath);
            
            // Create the asset
            $asset = new \craft\elements\Asset();
            $asset->tempFilePath = $tempPath;
            $asset->filename = $filename;
            $asset->newFolderId = $targetFolderId;
            $asset->volumeId = $targetFolder->volumeId;
            $asset->avoidFilenameConflicts = true;
            
            // Save the new asset
            Craft::$app->elements->saveElement($asset);
            
            // Clean up
            $imagick->destroy();
            unlink($tempPath);
            unlink($pdfPath);
            
        } catch (\Exception $e) {
            Craft::error('Failed to generate PDF thumbnail: ' . $e->getMessage(), __METHOD__);
        }
    }
}