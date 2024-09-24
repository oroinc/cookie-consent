<?php

namespace Oro\Bundle\CookieConsentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCookieConsentBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addCookiesAcceptedToCustomerUserTable($schema);
        $this->addCookiesAcceptedToCustomerVisitorTable($schema);
    }

    private function addCookiesAcceptedToCustomerUserTable(Schema $schema): void
    {
        $customerUserTable = $schema->getTable('oro_customer_user');
        $customerUserTable->addColumn(
            'cookies_accepted',
            'boolean',
            [
                'notnull' => true,
                'default' => false,
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'importexport' => ['excluded' => true],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN],
                    'form' => ['is_enabled' => false],
                    'email' => ['available_in_template' => false],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                ]
            ]
        );
    }

    private function addCookiesAcceptedToCustomerVisitorTable(Schema $schema): void
    {
        $customerVisitorTable = $schema->getTable('oro_customer_visitor');
        $customerVisitorTable->addColumn(
            'cookies_accepted',
            'boolean',
            [
                'notnull' => true,
                'default' => false,
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'importexport' => ['excluded' => true],
                    'datagrid'  => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN],
                    'form'      => ['is_enabled' => false],
                    'email' => ['available_in_template' => false],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                ]
            ]
        );
    }
}
