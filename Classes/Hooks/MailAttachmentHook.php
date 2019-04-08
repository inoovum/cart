<?php
declare(strict_types=1);
namespace Extcode\Cart\Hooks;

use Extcode\Cart\Domain\Model\Order\Item;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class MailAttachmentHook implements MailAttachmentHookInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $pluginSettings = [];

    /**
     * MailHandler constructor
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(
            ObjectManager::class
        );

        $this->configurationManager = $this->objectManager->get(
            ConfigurationManager::class
        );

        $this->pluginSettings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );
    }

    /**
     * @param MailMessage $mailMessage
     * @param Item $item
     * @param string $type = ['buyer' | 'seller']
     *
     * @return MailMessage
     */
    public function getMailAttachments(MailMessage $mailMessage, Item $item, string $type): MailMessage
    {
        if ($this->pluginSettings['mail'] && $this->pluginSettings['mail'][$type]) {
            if ($this->pluginSettings['mail'][$type]['attachments']) {
                $attachments = $this->pluginSettings['mail'][$type]['attachments'];

                foreach ($attachments as $attachment) {
                    $attachmentFile = GeneralUtility::getFileAbsFileName($attachment);
                    if (file_exists($attachmentFile)) {
                        $mailMessage->attach(\Swift_Attachment::fromPath($attachmentFile));
                    }
                }
            }

            if ($this->pluginSettings['mail'][$type]['attachDocuments']) {
                foreach ($this->pluginSettings['mail'][$type]['attachDocuments'] as $pdfType => $pdfData) {
                    $getter = 'get' . ucfirst($pdfType) . 'Pdfs';
                    $pdfs = $item->$getter();
                    if ($pdfs && ($pdfs instanceof ObjectStorage)) {
                        $pdfs = end($pdfs->toArray());
                        if ($pdfs) {
                            $lastOriginalPdf = $pdfs->getOriginalResource();
                            $lastOriginalPdfPath = PATH_site . $lastOriginalPdf->getPublicUrl();
                            if (is_file($lastOriginalPdfPath)) {
                                $mailMessage->attach(\Swift_Attachment::fromPath($lastOriginalPdfPath));
                            }
                        }
                    }
                }
            }
        }

        return $mailMessage;
    }
}