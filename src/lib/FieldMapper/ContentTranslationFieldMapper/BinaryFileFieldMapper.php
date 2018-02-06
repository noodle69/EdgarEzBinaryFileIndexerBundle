<?php

namespace Edgar\EzBinaryFileIndexer\FieldMapper\ContentTranslationFieldMapper;

use Enzim\Lib\TikaWrapper\TikaWrapper;
use eZ\Publish\Core\IO\IOService;
use EzSystems\EzPlatformSolrSearchEngine\FieldMapper\BoostFactorProvider;
use EzSystems\EzPlatformSolrSearchEngine\FieldMapper\ContentTranslationFieldMapper;
use eZ\Publish\Core\Search\Common\FieldRegistry;
use eZ\Publish\SPI\Persistence\Content;
use eZ\Publish\SPI\Persistence\Content\Type as ContentType;
use eZ\Publish\SPI\Persistence\Content\Type\Handler as ContentTypeHandler;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\FieldType;

class BinaryFileFieldMapper extends ContentTranslationFieldMapper
{
    /**
     * Field name, untyped.
     *
     * @var string
     */
    private static $fieldName = 'meta_content__text';

    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Type\Handler
     */
    protected $contentTypeHandler;

    /**
     * @var \eZ\Publish\Core\Search\Common\FieldRegistry
     */
    protected $fieldRegistry;

    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\FieldMapper\BoostFactorProvider
     */
    protected $boostFactorProvider;

    /** @var IOService */
    protected $ioService;

    /** @var string */
    protected $kernelRootDir;

    /**
     * BinaryFileFieldMapper constructor.
     *
     * @param ContentTypeHandler $contentTypeHandler
     * @param FieldRegistry $fieldRegistry
     * @param BoostFactorProvider $boostFactorProvider
     * @param IOService $ioService
     * @param string $kernelRootDir
     */
    public function __construct(
        ContentTypeHandler $contentTypeHandler,
        FieldRegistry $fieldRegistry,
        BoostFactorProvider $boostFactorProvider,
        IOService $ioService,
        string $kernelRootDir
    ) {
        $this->contentTypeHandler = $contentTypeHandler;
        $this->fieldRegistry = $fieldRegistry;
        $this->boostFactorProvider = $boostFactorProvider;
        $this->ioService = $ioService;
        $this->kernelRootDir = $kernelRootDir;
    }

    /**
     * Indicates if the mapper accepts given $content and $languageCode for mapping.
     *
     * @param Content $content
     * @param string $languageCode
     *
     * @return bool
     */
    public function accept(Content $content, $languageCode)
    {
        $fields = $content->fields;
        foreach ($fields as $field) {
            if ($field->type == 'ezbinaryfile') {
                return true;
            }
        }

        return false;
    }

    /**
     * Maps given $content for $languageCode to an array of search fields.
     *
     * @param Content $content
     * @param string $languageCode
     *
     * @return array|Field[]
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function mapFields(Content $content, $languageCode)
    {
        $fields = [];
        $contentType = $this->contentTypeHandler->load(
            $content->versionInfo->contentInfo->contentTypeId
        );

        $contentFields = $content->fields;
        /** @var Content\Field[] $contentBinaryFields */
        $contentBinaryFields = [];
        foreach ($contentFields as $field) {
            if ($field->type == 'ezbinaryfile') {
                $contentBinaryFields[] = $field;
            }
        }

        foreach ($contentBinaryFields as $field) {
            if ($field->languageCode !== $languageCode) {
                continue;
            }

            foreach ($contentType->fieldDefinitions as $fieldDefinition) {
                if ($fieldDefinition->id !== $field->fieldDefinitionId) {
                    continue;
                }

                $fieldType = $this->fieldRegistry->getType($field->type);
                $indexFields = $fieldType->getIndexData($field, $fieldDefinition);

                foreach ($indexFields as $indexField) {
                    if ($indexField->value === null) {
                        continue;
                    }

                    if ($indexField->name != 'file_name') {
                        continue;
                    }

                    $binaryFile = $this->ioService->loadBinaryFile($field->value->externalData['id']);
                    $binaryFilePath = $this->kernelRootDir . '/../web' . $binaryFile->uri;
                    $plaintext = TikaWrapper::getText($binaryFilePath);

                    $fields[] = new Field(
                        self::$fieldName,
                        $plaintext,
                        $this->getIndexFieldType($contentType)
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Return index field type for the given $contentType.
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Type $contentType
     *
     * @return \eZ\Publish\SPI\Search\FieldType
     */
    private function getIndexFieldType(ContentType $contentType)
    {
        $newFieldType = new FieldType\TextField();
        $newFieldType->boost = $this->boostFactorProvider->getContentMetaFieldBoostFactor(
            $contentType,
            'text'
        );

        return $newFieldType;
    }
}
