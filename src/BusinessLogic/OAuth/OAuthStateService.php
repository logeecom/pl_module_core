<?php

namespace Packlink\BusinessLogic\OAuth;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\BusinessLogic\OAuth\Exceptions\InvalidOAuthStateException;
use Packlink\BusinessLogic\OAuth\Interfaces\OAuthStateServiceInterface;
use Packlink\BusinessLogic\OAuth\Models\OAuthState;

class OAuthStateService implements OAuthStateServiceInterface
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param $tenantId
     *
     * @return string
     */
    public function generate($tenantId)
    {
        $random = hash('sha256', mt_rand() . uniqid('', true) . microtime(true));

        $data = array(
            'tenantId' => $tenantId,
            'state' => $random
        );

        return base64_encode(json_encode($data));
    }

    /**
     * @param $tenantId
     * @param $state
     *
     * @return void
     */
    public function saveState($tenantId, $state)
    {
        $stateEntity = new OAuthState();
        $stateEntity->setTenantId($tenantId);
        $stateEntity->setState($state);

        $this->repository->save($stateEntity);
    }

    /**
     * @param $encodedState
     *
     * @return mixed|string|null
     */
    public function extractTenantIdFromState($encodedState)
    {
        $decoded = base64_decode($encodedState);
        $data = json_decode($decoded, true);

        return is_array($data) && isset($data['tenantId']) ? $data['tenantId'] : null;
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws InvalidOAuthStateException
     *
     * @return bool
     */
    public function validateState($encodedState)
    {
        $decoded = base64_decode($encodedState);
        $data = json_decode($decoded, true);

        if (!is_array($data) || !isset($data['tenantId'], $data['state'])) {
            throw new InvalidOAuthStateException('Invalid state structure.');
        }

        $state = $this->getState($data['tenantId'], $data['state']);

        if ($state === null) {
            throw new InvalidOAuthStateException('State not found.');
        }

        return true;
    }

    /**
     * @param $tenantId
     * @param $state
     *
     * @return \Logeecom\Infrastructure\ORM\Entity|null
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getState($tenantId, $state)
    {
        $filter = new QueryFilter();
        $filter->where('tenantId', '=', $tenantId);
        $filter->where('state', '=', $state);

        return $this->repository->selectOne($filter);
    }
}