<?php

namespace Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CookieConsentBundle\Form\Type\ConfigLandingPageSelectType;
use Oro\Bundle\CookieConsentBundle\Tests\Unit\Form\Type\Stub\PageStub;
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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ConfigLandingPageSelectTypeTest extends FormIntegrationTestCase
{
    private const EXIST_PAGE_ID = 77;
    private const EXIST_PAGE_TITLE = 'SeventySeventhPage';

    private ManagerRegistry&MockObject $doctrine;
    private SearchRegistry&MockObject $searchRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $pageMetaData = $this->createMock(ClassMetadata::class);
        $pageMetaData->expects(self::any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($pageMetaData);

        $existPage = $this->getPageStub(self::EXIST_PAGE_ID, self::EXIST_PAGE_TITLE);
        $entityManager->expects(self::any())
            ->method('find')
            ->with(Page::class, self::EXIST_PAGE_ID)
            ->willReturn($existPage);

        $pageRepository = $this->createMock(EntityRepository::class);
        $pageRepository->expects(self::any())
            ->method('find')
            ->with(self::EXIST_PAGE_ID)
            ->willReturn($existPage);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Page::class)
            ->willReturn($pageRepository);

        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects(self::any())
            ->method('getProperties')
            ->willReturn([]);

        $this->searchRegistry = $this->createMock(SearchRegistry::class);
        $this->searchRegistry->expects(self::any())
            ->method('getSearchHandler')
            ->willReturn($searchHandler);

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
                $this->doctrine,
                $this->searchRegistry,
                $this->createMock(ConfigProvider::class)
            ),
            new OroEntitySelectOrCreateInlineType(
                $this->createMock(AuthorizationCheckerInterface::class),
                $this->createMock(FeatureChecker::class),
                $configManager,
                $this->doctrine,
                $this->searchRegistry
            ),
            new ConfigLandingPageSelectType($this->doctrine)
        ];
    }

    public function testFormDefaultProps()
    {
        $form = $this->factory->create(ConfigLandingPageSelectType::class);
        self::assertEquals(ConfigLandingPageSelectType::NAME, $form->getName());
        self::assertFalse($form->isRequired());
        $viewVars = $form->createView()->vars;
        self::assertArrayHasKey('value', $viewVars);
        self::assertEmpty($viewVars['value']);
    }

    public function testExistPageSelected()
    {
        $form = $this->factory->create(ConfigLandingPageSelectType::class, self::EXIST_PAGE_ID);
        $viewVars = $form->createView()->vars;
        self::assertArrayHasKey('value', $viewVars);
        self::assertEquals((string)self::EXIST_PAGE_ID, $viewVars['value']);

        self::assertArrayHasKey('attr', $viewVars);
        $attr = $viewVars['attr'];
        self::assertArrayHasKey('data-selected-data', $attr);

        $dataSelectData = json_decode($attr['data-selected-data'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($dataSelectData);

        self::assertArrayHasKey('id', $dataSelectData);
        self::assertArrayHasKey('defaultTitle.string', $dataSelectData);

        self::assertEquals(self::EXIST_PAGE_ID, $dataSelectData['id']);
        self::assertEquals(self::EXIST_PAGE_TITLE, $dataSelectData['defaultTitle.string']);
    }
}
