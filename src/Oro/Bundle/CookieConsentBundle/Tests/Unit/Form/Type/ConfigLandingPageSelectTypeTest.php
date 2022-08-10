<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CookieConsentBundle\Form\Type\ConfigLandingPageSelectType;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type\Stub\PageStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
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

    /**@var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $searchRegistry;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::any())
            ->method('has')
            ->with('grid_name')
            ->willReturn(true);
        $config->expects(self::any())
            ->method('get')
            ->with('grid_name')
            ->willReturn('some_grid');

        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->configProvider->expects(self::any())
            ->method('getConfig')
            ->willReturn($config);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects(self::any())
            ->method('getProvider')
            ->with('form')
            ->willReturn($this->configProvider);

        $pageMetaData = $this->createMock(ClassMetadata::class);
        $pageMetaData->expects(self::any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($pageMetaData);

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

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return [
            new Select2Type('', ''),
            new OroJquerySelect2HiddenType(
                $this->entityManager,
                $this->searchRegistry,
                $this->configProvider
            ),
            new OroEntitySelectOrCreateInlineType(
                $this->createMock(AuthorizationCheckerInterface::class),
                $this->createMock(FeatureChecker::class),
                $this->configManager,
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
