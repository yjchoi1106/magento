<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\File;

use Magento\Customer\Model\FileProcessor;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Test for \Magento\Framework\File\Uploader
 */
class UploaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->uploaderFactory = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get(\Magento\MediaStorage\Model\File\UploaderFactory::class);

        $this->filesystem = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get(\Magento\Framework\Filesystem::class);
    }

    /**
     * @return void
     */
    public function testUploadFileFromAllowedFolder(): void
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $fileName = 'text.txt';
        $uploader = $this->createUploader($fileName);

        $uploader->save($mediaDirectory->getAbsolutePath(FileProcessor::TMP_DIR));

        $this->assertTrue($mediaDirectory->isFile(FileProcessor::TMP_DIR . DIRECTORY_SEPARATOR . $fileName));
    }

    /**
     * @return void
     */
    public function testUploadFileFromNotAllowedFolder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid parameter given. A valid $fileId[tmp_name] is expected.');

        $fileName = 'text.txt';
        $tmpDir = 'tmp';
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::LOG);
        $filePath = $tmpDirectory->getAbsolutePath() . $tmpDir . DIRECTORY_SEPARATOR . $fileName;

        $tmpDirectory->writeFile($tmpDir . DIRECTORY_SEPARATOR . $fileName, 'just a text');

        $type = [
            'tmp_name' => $filePath,
            'name' => $fileName,
        ];

        $this->uploaderFactory->create(['fileId' => $type]);
    }

    /**
     * @return void
     */
    public function testUploadFileWithExcessiveFolderName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination folder path is too long; must be 255 characters or less');

        $uploader = $this->createUploader('text.txt');

        // phpcs:disable Generic.Files.LineLength.TooLong
        $longDirectoryFolderName = 'wysiwyg////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////long/destination/folder/name';
        // phpcs:enable Generic.Files.LineLength.TooLong

        $uploader->save($longDirectoryFolderName);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $tmpDir = 'tmp';
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $mediaDirectory->delete($tmpDir);

        $logDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::LOG);
        $logDirectory->delete($tmpDir);
    }

    /**
     * Create uploader instance for testing purposes.
     *
     * @param string $fileName
     *
     * @return Uploader
     */
    private function createUploader(string $fileName): Uploader
    {
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);

        $filePath = $tmpDirectory->getAbsolutePath($fileName);

        $tmpDirectory->writeFile($fileName, 'just a text');

        $type = [
            'tmp_name' => $filePath,
            'name' => $fileName,
        ];

        return $this->uploaderFactory->create(['fileId' => $type]);
    }
}
