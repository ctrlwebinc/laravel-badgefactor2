<?php

namespace Ctrlweb\BadgeFactor2\Helpers;

/**
 * @tags Facebook
 */
class LinkedIn
{
    public static function generateLink(string $badgeName, int $issueYear, int $issueMonth, string $assertionUrl, string $assertionId, string $organizationName = null)
    {
        $query = http_build_query([
            'startTask'        => 'CERTIFICATION_NAME',
            'name'             => $badgeName,
            'issueYear'        => $issueYear,
            'issueMonth'       => $issueMonth,
            'certUrl'          => $assertionUrl,
            'certId'           => $assertionId,
            'organizationName' => $organizationName ?? config('app.name'),
        ]);
        $sessionRedirect = urlencode(sprintf('https://www.linkedin.com/profile/add?%s', $query));

        return sprintf('https://www.linkedin.com/uas/login?session_redirect=%s', $sessionRedirect);
    }

}
