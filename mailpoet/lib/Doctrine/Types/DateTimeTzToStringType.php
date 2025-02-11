<?php

namespace MailPoet\Doctrine\Types;

use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Platforms\AbstractPlatform;
use MailPoetVendor\Doctrine\DBAL\Types\DateTimeTzType;

class DateTimeTzToStringType extends DateTimeTzType {
  const NAME = 'datetimetz_to_string';

  public function convertToPHPValue($value, AbstractPlatform $platform) {
    $dateTime = parent::convertToPHPValue($value, $platform);

    if (!$dateTime) {
      return $dateTime;
    }

    return Carbon::instance($dateTime);
  }

  public function requiresSQLCommentHint(AbstractPlatform $platform): bool {
    return true;
  }

  public function getName(): string {
    return self::NAME;
  }
}
