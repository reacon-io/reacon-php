<?php
namespace Reacon\Sdk\Model;
/** Generated typed union; select a native branch model. */
final class ProductToolRequestInput extends ObjectUnion
{
    protected const VARIANTS = [
        ['class' => 'Reacon\\Sdk\\Model\\ProductDiscoverCompaniesInput', 'empty' => false, 'closed' => true, 'keys' => ['industry', 'limit', 'location', 'query'], 'required' => [], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductDiscoverPeopleInput', 'empty' => false, 'closed' => true, 'keys' => ['domain', 'jobTitle', 'limit', 'query'], 'required' => [], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductDomainFinderInput', 'empty' => false, 'closed' => true, 'keys' => ['company'], 'required' => ['company'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductEmailCountInput', 'empty' => false, 'closed' => true, 'keys' => ['domain'], 'required' => ['domain'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductPersonEnrichInput', 'empty' => false, 'closed' => true, 'keys' => ['email'], 'required' => ['email'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductSavedSearchesListInput', 'empty' => true, 'closed' => true, 'keys' => [], 'required' => [], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadsListInput', 'empty' => false, 'closed' => true, 'keys' => ['limit', 'listId', 'offset'], 'required' => [], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadGetInput', 'empty' => false, 'closed' => true, 'keys' => ['leadId'], 'required' => ['leadId'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadCreateInput', 'empty' => false, 'closed' => true, 'keys' => ['attributes', 'company', 'email', 'firstName', 'idempotencyKey', 'lastName', 'position'], 'required' => ['email', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadUpdateInput', 'empty' => false, 'closed' => true, 'keys' => ['attributes', 'company', 'firstName', 'idempotencyKey', 'lastName', 'leadId', 'position'], 'required' => ['leadId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadDeleteInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'leadId'], 'required' => ['leadId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadBulkDeleteInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'leadIds'], 'required' => ['leadIds', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadTagCreateInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'name'], 'required' => ['name', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadTagAssignInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'leadId', 'tagId'], 'required' => ['leadId', 'tagId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductCustomAttributeCreateInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'key', 'name'], 'required' => ['name', 'key', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadListUpdateInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'listId', 'name'], 'required' => ['listId', 'name', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadListDeleteInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'listId'], 'required' => ['listId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductLeadListAddLeadInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'leadId', 'listId'], 'required' => ['listId', 'leadId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductCompaniesListInput', 'empty' => false, 'closed' => true, 'keys' => ['limit', 'offset'], 'required' => [], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductCompanyTrackInput', 'empty' => false, 'closed' => true, 'keys' => ['domain', 'idempotencyKey', 'name'], 'required' => ['domain', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductCompanyUpdateInput', 'empty' => false, 'closed' => true, 'keys' => ['companyId', 'employeeRange', 'idempotencyKey', 'industry', 'name'], 'required' => ['companyId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductCompanyDeleteInput', 'empty' => false, 'closed' => true, 'keys' => ['companyId', 'idempotencyKey'], 'required' => ['companyId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductCompanyListAddInput', 'empty' => false, 'closed' => true, 'keys' => ['companyId', 'idempotencyKey', 'listId'], 'required' => ['listId', 'companyId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductSequenceRecipientsListInput', 'empty' => false, 'closed' => true, 'keys' => ['sequenceId'], 'required' => ['sequenceId'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductSequenceRecipientsAddInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'recipients', 'sequenceId'], 'required' => ['sequenceId', 'recipients', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductSequenceRecipientAddInput', 'empty' => false, 'closed' => true, 'keys' => ['email', 'idempotencyKey', 'leadId', 'sequenceId'], 'required' => ['sequenceId', 'email', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductSequenceRecipientCancelInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'recipientId'], 'required' => ['recipientId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductSequenceStartInput', 'empty' => false, 'closed' => true, 'keys' => ['idempotencyKey', 'sequenceId'], 'required' => ['sequenceId', 'idempotencyKey'], 'tags' => []],
        ['class' => 'Reacon\\Sdk\\Model\\ProductConnectedAppPushInput', 'empty' => false, 'closed' => true, 'keys' => ['connectionId', 'idempotencyKey', 'leadIds'], 'required' => ['connectionId', 'leadIds', 'idempotencyKey'], 'tags' => []],
    ];
    public function __construct(ProductDiscoverCompaniesInput|ProductDiscoverPeopleInput|ProductDomainFinderInput|ProductEmailCountInput|ProductPersonEnrichInput|\stdClass|ProductLeadsListInput|ProductLeadGetInput|ProductLeadCreateInput|ProductLeadUpdateInput|ProductLeadDeleteInput|ProductLeadBulkDeleteInput|ProductLeadTagCreateInput|ProductLeadTagAssignInput|ProductCustomAttributeCreateInput|ProductLeadListUpdateInput|ProductLeadListDeleteInput|ProductLeadListAddLeadInput|ProductCompaniesListInput|ProductCompanyTrackInput|ProductCompanyUpdateInput|ProductCompanyDeleteInput|ProductCompanyListAddInput|ProductSequenceRecipientsListInput|ProductSequenceRecipientsAddInput|ProductSequenceRecipientAddInput|ProductSequenceRecipientCancelInput|ProductSequenceStartInput|ProductConnectedAppPushInput $value) { parent::__construct($value); }
}
