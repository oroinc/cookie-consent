<?php

namespace Oro\Bundle\CookieConsentBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Form\Type\PageSelectType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select FormType that is used to Select specific cms LandingPageId for
 * Config parameter
 */
class ConfigLandingPageSelectType extends AbstractType
{
    public const NAME = 'cookie_consent_config_landing_page_select';

    public const OPTION_NAME_ENTITY_ID_PROP = 'entity_id_property';
    public const OPTION_NAME_ENTITY_TITLE_PROP = 'entity_title_property';

    public const OPTION_ENTITY_ID_PROP_DEFAULT = 'id';
    public const OPTION_ENTITY_TITLE_PROP_DEFAULT = 'getDefaultTitle';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'entity_class' => Page::class,
                'create_enabled' => false,
                self::OPTION_NAME_ENTITY_ID_PROP => self::OPTION_ENTITY_ID_PROP_DEFAULT,
                self::OPTION_NAME_ENTITY_TITLE_PROP => self::OPTION_ENTITY_TITLE_PROP_DEFAULT
            ])
        ;
    }

    /**
     * Needed because we need "reverse" transform logic:
     * FormData is int $pageId, in transform, we need to get PageEntity
     * and we need int $pageId, for reverseTransform
     *
     * @param string $entityClass
     * @param string $entityIdProp
     * @return CallbackTransformer
     * @throws \Symfony\Component\Form\Exception\UnexpectedTypeException
     */
    private function createReverseTransformer(string $entityClass, string $entityIdProp)
    {
        $entityToIdTransformer = new EntityToIdTransformer(
            $this->doctrineHelper->getEntityManagerForClass($entityClass),
            $entityClass,
            $entityIdProp
        );

        return new CallbackTransformer(
            function ($value) use ($entityToIdTransformer) {
                if (\is_numeric($value)) {
                    try {
                        $value = $entityToIdTransformer->reverseTransform((int)$value);
                    } catch (TransformationFailedException $e) {
                        return null;
                    }
                } elseif ('' === $value) {
                    $value = null;
                }

                return $value;
            },
            function ($value) use ($entityToIdTransformer) {
                if (\is_object($value)) {
                    return $entityToIdTransformer->transform($value);
                }

                return $value;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            $this->createReverseTransformer(
                $options['entity_class'],
                $options[self::OPTION_NAME_ENTITY_ID_PROP]
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PageSelectType::class;
    }

    /**
     * @param object $entity
     * @param array $options
     *
     * @return string
     */
    private function makeSelectViewSerializedData($entity, array $options): string
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $id = $propertyAccessor->getValue($entity, $options[self::OPTION_NAME_ENTITY_ID_PROP]);
        $title = $propertyAccessor->getValue($entity, $options[self::OPTION_NAME_ENTITY_TITLE_PROP]);

        return \json_encode([
            'id' => $id,
            'defaultTitle.string' => (string)$title
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $value = $view->vars['value'];
        if ($value) {
            $page = $this->doctrineHelper
                ->getEntityManagerForClass($options['entity_class'])
                ->find($options['entity_class'], $value)
            ;
            if (null !== $page) {
                $view->vars['attr']['data-selected-data'] = $this->makeSelectViewSerializedData($page, $options);
            }
        }
    }
}
