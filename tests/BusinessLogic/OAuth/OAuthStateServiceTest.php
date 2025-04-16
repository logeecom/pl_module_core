<?php

namespace BusinessLogic\OAuth;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Packlink\BusinessLogic\OAuth\Exceptions\InvalidOAuthStateException;
use Packlink\BusinessLogic\OAuth\Models\OAuthState;
use Packlink\BusinessLogic\OAuth\OAuthStateService;

class OAuthStateServiceTest extends BaseTestWithServices
{
    /**
     * OAuthState service instance.
     *
     * @var OAuthStateService
     */
    public $service;

    /**
     * @var MemoryRepository
     */
    public $repository;

    /**
     * @throws RepositoryNotRegisteredException
     * @throws RepositoryClassException
     */
    protected function setUp()
    {
        RepositoryRegistry::registerRepository(OAuthState::CLASS_NAME, MemoryRepository::getClassName());

        $this->repository = RepositoryRegistry::getRepository(OAuthState::CLASS_NAME);

        $this->service = new OAuthStateService($this->repository);
    }

    public function testGenerateReturnsValidBase64String()
    {
        $state = $this->service->generate('test-tenant');

        $decoded = json_decode(base64_decode($state), true);

        $this->assertArrayHasKey('tenantId', $decoded);
        $this->assertArrayHasKey('state', $decoded);
        $this->assertEquals('test-tenant', $decoded['tenantId']);
    }

    /**
     * Test for the extractTenantIdFromState() method
     */
    public function testExtractTenantIdFromState()
    {
        $tenantId = 'tenant_123';
        $encodedState = $this->service->generate($tenantId);

        $extractedTenantId = $this->service->extractTenantIdFromState($encodedState);

        $this->assertEquals($tenantId, $extractedTenantId);
    }

    /**
     * Test for the saveState() method
     *
     * @throws QueryFilterInvalidParamException
     */
    public function testSaveState()
    {
        $tenantId = 'tenant_123';
        $randState = 'random_state_value';

        $this->service->saveState($tenantId, $randState);

        $state = $this->service->getState($tenantId, $randState);

        $this->assertEquals($tenantId, $state->getTenantId());
        $this->assertEquals($randState, $state->getState());
    }

    public function testValidateStateWithValidData()
    {
        $tenantId = 'tenant_456';
        $state = 'valid_random_state';

        $this->service->saveState($tenantId, $state);

        $encodedState = base64_encode(json_encode(array(
            'tenantId' => $tenantId,
            'state' => $state
        )));

        $result = $this->service->validateState($encodedState);

        $this->assertTrue($result);
    }

    public function testValidateStateThrowsExceptionForInvalidStructure()
    {
        $encodedState = base64_encode(json_encode(array('invalid_key' => 'value')));

        try {
            $this->service->validateState($encodedState);
            $this->fail('Expected InvalidOAuthStateException was not thrown.');
        } catch (InvalidOAuthStateException $e) {
            $this->assertEquals('Invalid state structure.', $e->getMessage());
        }
    }

    public function testValidateStateThrowsExceptionForMissingState()
    {
        $encodedState = base64_encode(json_encode(array(
            'tenantId' => 'non_existent_tenant',
            'state' => 'non_existent_state'
        )));

        try {
            $this->service->validateState($encodedState);
            $this->fail('Expected InvalidOAuthStateException was not thrown.');
        } catch (InvalidOAuthStateException $e) {
            $this->assertEquals('State not found.', $e->getMessage());
        }
    }
}