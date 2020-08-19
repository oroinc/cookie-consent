<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CookieConsentBundle\Form\Type\ConfigLandingPageSelectType;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type\Stub\PageStub;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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
    protected $configProvider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $searchRegistry;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('has')->with('grid_name')->willReturn(true);
        $config->method('get')->with('grid_name')->willReturn('some_grid');
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->configProvider->method('getConfig')->willReturn($config);

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager
            ->method('getProvider')
            ->with('form')
            ->willReturn($this->configProvider)
        ;
        $this->entityManager = $this->createMock(EntityManager::class);
        $pageMetaData = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $pageMetaData->method('getSingleIdentifierFieldName')->willReturn('id');
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($pageMetaData)
        ;
        $existPage = $this->getPageStub(self::EXIST_PAGE_ID, self::EXIST_PAGE_TITLE);
        $this->entityManager
            ->method('find')
            ->with(Page::class, self::EXIST_PAGE_ID)
            ->willReturn($existPage)
        ;
        $pageRepository = $this->createMock(EntityRepository::class);
        $pageRepository->method('find')->with(self::EXIST_PAGE_ID)->willReturn($existPage);
        $this->entityManager
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($pageRepository)
        ;
        $this->searchRegistry = $this->createMock(SearchRegistry::class);

        $searchHandlerMock = $this->createMock(SearchHandlerInterface::class);
        $searchHandlerMock
            ->method('getProperties')
            ->willReturn([])
        ;
        $this->searchRegistry
            ->method('getSearchHandler')
            ->willReturn($searchHandlerMock)
        ;

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineHelper
            ->method('getEntityManagerForClass')
            ->with(Page::class)
            ->willReturn($this->entityManager)
        ;

        parent::setUp();
    }

    /**
     * @param int $id
     * @param string $title
     * @return Page
     */
    private function getPageStub($id, $title): Page
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
                $this->authorizationChecker,
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

        $dataSelectData = \json_decode($attr['data-selected-data'], true);
        static::assertIsArray($dataSelectData);

        static::assertArrayHasKey('id', $dataSelectData);
        static::assertArrayHasKey('defaultTitle.string', $dataSelectData);

        static::assertEquals(self::EXIST_PAGE_ID, $dataSelectData['id']);
        static::assertEquals(self::EXIST_PAGE_TITLE, $dataSelectData['defaultTitle.string']);
    }
}
