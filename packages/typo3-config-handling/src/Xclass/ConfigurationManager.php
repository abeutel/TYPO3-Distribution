<?php
namespace Helhum\TYPO3\ConfigHandling\Xclass;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Helmut Hummel <info@helhum.io>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Helhum\TYPO3\ConfigHandling\ConfigDumper;
use Helhum\TYPO3\ConfigHandling\ConfigExtractor;
use Helhum\TYPO3\ConfigHandling\ConfigLoader;
use Helhum\TYPO3\ConfigHandling\RootConfig;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationManager extends \TYPO3\CMS\Core\Configuration\ConfigurationManager
{
    /**
     * @var ConfigExtractor
     */
    private $configExtractor;

    /**
     * @var ConfigDumper
     */
    private $configDumper;

    private $activeConfig;

    public function __construct(ConfigExtractor $configExtractor = null, ConfigDumper $configDumper = null)
    {
        $this->configDumper = $configDumper ?: new ConfigDumper();
        $this->configExtractor = $configExtractor ?: new ConfigExtractor($this->configDumper);
        $this->activeConfig = (new ConfigLoader(RootConfig::getRootConfigFile()))->load();
    }

    public function getLocalConfiguration()
    {
        return $this->activeConfig;
    }

    public function getLocalConfigurationValueByPath($path)
    {
        return ArrayUtility::getValueByPath($this->activeConfig, $path);
    }

    public function writeLocalConfiguration(array $configuration)
    {
        $localConfigurationFile = $this->getLocalConfigurationFileLocation();
        if (!$this->canWriteConfiguration()) {
            throw new \RuntimeException(
                $localConfigurationFile . ' is not writable.', 1346323822
            );
        }
        $this->configExtractor->extractConfig($configuration, $this->getDefaultConfiguration());
        $configuration = GeneralUtility::getApplicationContext()->isProduction() ? $configuration : [];
        return $this->configDumper->dumpToFile($configuration, $localConfigurationFile, "Auto generated by helhum/typo3-config-handling\nDo not edit this file");
    }
}
