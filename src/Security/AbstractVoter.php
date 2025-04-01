<?php

declare(strict_types=1);

namespace LibertJeremy\Symfony\SecurityHelpersBundle\Security;

use LibertJeremy\Symfony\SecurityHelpers\Traits\VoterHelperTrait;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class AbstractVoter extends Voter
{
    use VoterHelperTrait;
}
