<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;
use App\Imperium\Runtime\Clavium\FormationClaimCustodyBroker;

/** Dormant integration seam. BoundedFormationTransport's production alias stays refusing. */
final readonly class CustodiedFormationTransport implements PreparedFormationTransport
{
    public function __construct(private FormationWireAdapter $adapter, private FormationClaimCustodyBroker $custody) {}
    public function prepareOperation(array $request, array $terms): array
    {
        $operation = $this->adapter->prepare($request,$terms);
        FormationPreparedOperation::validate($operation,$request,$terms,$operation['maximum'] ?? []);
        return $operation;
    }
    public function inspect(array $request, array $terms): array { return $this->prepareOperation($request,$terms)['maximum']; }
    public function invoke(array $claim, array $request, array $terms): array { return $this->custody->invoke($claim,$request,$terms); }
}
