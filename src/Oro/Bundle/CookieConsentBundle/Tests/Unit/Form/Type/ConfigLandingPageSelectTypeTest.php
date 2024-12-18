<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CookieConsentBundle\Form\Type\ConfigLandingPageSelectType;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type\Stub\PageStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ConfigLandingPageSelectTypeTest extends FormIntegrationTestCase
{
    private const EXIST_PAGE_ID = 77;
    private const EXIST_PAGE_TITLE = 'SeventySeventhPage';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $searchRegistry;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        $pageMetaData = $this->createMock(ClassMetadata::class);
        $pageMetaData->expects(self::any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($pageMetaData);
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $this->entityManager->expects(self::any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::any())
            ->method('hasMetadataFor')
            ->willReturn(true);

        $existPage = $this->getPageStub(self::EXIST_PAGE_ID, self::EXIST_PAGE_TITLE);
        $this->entityManager->expects(self::any())
            ->method('find')
            ->with(Page::class, self::EXIST_PAGE_ID)
            ->willReturn($existPage);

        $pageRepository = $this->createMock(EntityRepository::class);
        $pageRepository->expects(self::any())
            ->method('find')
            ->with(self::EXIST_PAGE_ID)
            ->willReturn($existPage);
        $this->entityManager->expects(self::any())
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($pageRepository);

        $searchHandlerMock = $this->createMock(SearchHandlerInterface::class);
        $searchHandlerMock->expects(self::any())
            ->method('getProperties')
            ->willReturn([]);

        $this->searchRegistry = $this->createMock(SearchRegistry::class);
        $this->searchRegistry->expects(self::any())
            ->method('getSearchHandler')
            ->willReturn($searchHandlerMock);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with(Page::class)
            ->willReturn($this->entityManager);

        parent::setUp();
    }

    private function getPageStub(int $id, string $title): Page
    {
        return (new PageStub())->setId($id)->setDefaultTitle($title);
    }

    #[\Override]
    public function getTypes()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturn(true);
        $configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('form')
            ->willReturnCallback(function ($className) {
                return new Config(new EntityConfigId('form', $className), ['grid_name' => 'test_grid']);
            });

        return [
            new Select2Type('', ''),
            new OroJquerySelect2HiddenType(
                $this->entityManager,
                $this->searchRegistry,
                $this->createMock(ConfigProvider::class)
            ),
            new OroEntitySelectOrCreateInlineType(
                $this->createMock(AuthorizationCheckerInterface::class),
                $this->createMock(FeatureChecker::class),
                $configManager,
                $this->entityManager,
                $this->searchRegistry
            ),
            new ConfigLandingPageSelectType($this->doctrineHelper)
        ];
    }

    public function testFormDefaultProps()
    {
        $form = $this->factory->create(ConfigLandingPageSelectType::class);
        static::assertEquals(ConfigLandingPageSelectType::NAME, $form->getName());
        static::assertFalse($form->isRequired());
        $viewVars = $form->createView()->vars;
        static::assertArrayHasKey('value', $viewVars);
        static::assertEmpty($viewVars['value']);
    }

    public function testExistPageSelected()
    {
        $form = $this->factory->create(ConfigLandingPageSelectType::class, self::EXIST_PAGE_ID);
        $viewVars = $form->createView()->vars;
        static::assertArrayHasKey('value', $viewVars);
        static::assertEquals((string)self::EXIST_PAGE_ID, $viewVars['value']);

        static::assertArrayHasKey('attr', $viewVars);
        $attr = $viewVars['attr'];
        static::assertArrayHasKey('data-selected-data', $attr);

        $dataSelectData = json_decode($attr['data-selected-data'], true, 512, JSON_THROW_ON_ERROR);
        static::assertIsArray($dataSelectData);

        static::assertArrayHasKey('id', $dataSelectData);
        static::assertArrayHasKey('defaultTitle.string', $dataSelectData);

        static::assertEquals(self::EXIST_PAGE_ID, $dataSelectData['id']);
        static::assertEquals(self::EXIST_PAGE_TITLE, $dataSelectData['defaultTitle.string']);
    }
}
