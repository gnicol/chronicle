# Class: proxified
#
# This module manages proxified
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
# [Remember: No empty lines between comments and class definition]
class proxified ($set_environment = 'true'){
  if ($set_environment) {
    include proxified::environment
  }

  case $operatingsystem {
    /(Ubuntu|Debian)/: {
      include proxified::apt
    }
  }
}
