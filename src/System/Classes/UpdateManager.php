<?php

namespace Igniter\System\Classes;

use Carbon\Carbon;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Igniter;
use Igniter\Flame\Mail\Markdown;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Models\Theme;
use Igniter\System\Models\Extension;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

/**
 * TastyIgniter Updates Manager Class
 */
class UpdateManager
{
    protected $logs = [];

    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected $logsOutput;

    protected $baseDirectory;

    protected $tempDirectory;

    protected $logFile;

    protected $updatedFiles;

    protected $installedItems;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var HubManager
     */
    protected $hubManager;

    /**
     * @var ExtensionManager
     */
    protected $extensionManager;

    /**
     * @var \Igniter\Flame\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * @var \Igniter\Flame\Database\Migrations\DatabaseMigrationRepository
     */
    protected $repository;

    protected $disableCoreUpdates;

    public function initialize()
    {
        $this->hubManager = resolve(HubManager::class);
        $this->themeManager = resolve(ThemeManager::class);
        $this->extensionManager = resolve(ExtensionManager::class);

        $this->tempDirectory = temp_path();
        $this->baseDirectory = base_path();
        $this->disableCoreUpdates = config('igniter.system.disableCoreUpdates', false);

        $this->bindContainerObjects();
    }

    public function bindContainerObjects()
    {
        $this->migrator = App::make('migrator');
        $this->repository = App::make('migration.repository');
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param \Illuminate\Console\OutputStyle $output
     * @return $this
     */
    public function setLogsOutput($output)
    {
        $this->logsOutput = $output;
        $this->migrator->setOutput($output);

        return $this;
    }

    public function log($message)
    {
        if (!is_null($this->logsOutput))
            $this->logsOutput->writeln($message);

        $this->logs[] = $message;

        return $this;
    }

    /**
     * @return \Igniter\System\Classes\UpdateManager $this
     */
    public function resetLogs()
    {
        $this->logs = [];

        return $this;
    }

    public function getLogs()
    {
        return $this->logs;
    }

    //
    //
    //

    public function down()
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        // Rollback extensions
        foreach (array_keys(Igniter::migrationPath()) as $code) {
            $this->purgeExtension($code);
        }

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        foreach (array_reverse(Igniter::coreMigrationPath(), true) as $group => $path) {
            $this->log("<info>Rolling back $group</info>");

            $this->migrator->resetAll([$group => $path]);

            $this->log("<info>Rolled back $group</info>");
        }

        $this->repository->deleteRepository();

        return $this;
    }

    public function update()
    {
        $wasPreviouslyMigrated = $this->prepareDatabase();

        $this->migrateApp();

        if (!$wasPreviouslyMigrated) {
            $this->seedApp();
        }

        foreach (array_keys(Igniter::migrationPath()) as $code) {
            $this->migrateExtension($code);
        }
    }

    public function setCoreVersion($version = null, $hash = null)
    {
        params()
            ->set('ti_version', $version ?? resolve(PackageManifest::class)->coreVersion())
            ->save();
    }

    protected function prepareDatabase()
    {
        $migrationTable = Config::get('database.migrations', 'migrations');

        if ($hasColumn = Schema::hasColumns($migrationTable, ['group', 'batch'])) {
            $this->repository->updateRepositoryGroup();

            $this->log('Migration table already exists');

            return true;
        }

        $this->repository->createRepository();

        $action = $hasColumn ? 'updated' : 'created';
        $this->log("Migration table {$action}");
    }

    public function migrateApp()
    {
        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        foreach (Igniter::coreMigrationPath() as $group => $path) {
            $this->log("<info>Migrating $group</info>");

            $this->migrator->runGroup([$group => $path]);

            $this->log("<info>Migrated $group</info>");
        }

        return $this;
    }

    public function seedApp()
    {
        Artisan::call('db:seed', [
            '--class' => '\Igniter\System\Database\Seeds\DatabaseSeeder',
            '--force' => true,
        ]);

        $this->log('<info>Seeded app</info> ');

        return $this;
    }

    public function migrateExtension($name)
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        if (!$this->extensionManager->findExtension($name))
            return $this->log('<error>Unable to find:</error> '.$name);

        $this->log("<info>Migrating extension $name</info>");

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        $this->migrator->runGroup(array_only(Igniter::migrationPath(), $name));

        $this->log("<info>Migrated extension $name</info>");

        return $this;
    }

    public function purgeExtension($name)
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        if (!$this->extensionManager->findExtension($name))
            return $this->log('<error>Unable to find:</error> '.$name);

        $this->log("<info>Purging extension $name</info>");

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        $this->migrator->rollDown(array_only(Igniter::migrationPath(), $name));

        $this->log("<info>Purged extension $name</info>");

        return $this;
    }

    public function rollbackExtension($name, array $options = [])
    {
        if (!$this->migrator->repositoryExists())
            return $this->log('<error>Migration table not found.</error>');

        if (!$this->extensionManager->findExtension($name))
            return $this->log('<error>Unable to find:</error> '.$name);

        if ($this->logsOutput)
            $this->migrator->setOutput($this->logsOutput);

        $this->migrator->rollbackAll(array_only(Igniter::migrationPath(), $name), $options);

        $this->log("<info>Rolled back extension $name</info>");

        return $this;
    }

    //
    //
    //

    public function isLastCheckDue()
    {
        $response = $this->requestUpdateList();

        if (isset($response['last_check'])) {
            return strtotime('-7 day') < strtotime($response['last_check']);
        }

        return true;
    }

    public function listItems($itemType)
    {
        $installedItems = $this->getInstalledItems();

        $items = $this->getHubManager()->listItems([
            'browse' => 'recommended',
            'limit' => 12,
            'type' => $itemType,
        ]);

        $installedItems = array_column($installedItems, 'name');
        if (isset($items['data'])) foreach ($items['data'] as &$item) {
            $item['icon'] = generate_extension_icon($item['icon'] ?? []);
            $item['installed'] = in_array($item['code'], $installedItems);
        }

        return $items;
    }

    public function searchItems($itemType, $searchQuery)
    {
        $installedItems = $this->getInstalledItems();

        $items = $this->getHubManager()->listItems([
            'type' => $itemType,
            'search' => $searchQuery,
        ]);

        $installedItems = array_column($installedItems, 'name');
        if (isset($items['data'])) foreach ($items['data'] as &$item) {
            $item['icon'] = generate_extension_icon($item['icon'] ?? []);
            $item['installed'] = in_array($item['code'], $installedItems);
        }

        return $items;
    }

    public function getSiteDetail()
    {
        return params('carte_info');
    }

    public function applySiteDetail($key)
    {
        $info = [];

        $this->setSecurityKey($key, $info);

        $result = $this->getHubManager()->getDetail('site');
        if (isset($result['data']) && is_array($result['data']))
            $info = $result['data'];

        $this->setSecurityKey($key, $info);

        return $info;
    }

    public function requestUpdateList($force = false)
    {
        $installedItems = $this->getInstalledItems();

        $updates = $this->hubManager->applyItemsToUpdate($installedItems, $force);

        if (is_string($updates))
            return $updates;

        $result = $items = $ignoredItems = [];
        $result['last_check'] = $updates['check_time'] ?? Carbon::now()->toDateTimeString();

        $installedItems = collect($installedItems)->keyBy('name')->all();

        $updateCount = 0;
        foreach (array_get($updates, 'data', []) as $update) {
            $updateCount++;
            $update['installedVer'] = array_get(array_get($installedItems, $update['code'], []), 'ver');

            $update = $this->parseTagDescription($update);
            $update['icon'] = generate_extension_icon($update['icon'] ?? []);

            if (array_get($update, 'type') == 'core') {
                $update['installedVer'] = params('ti_version');
                if ($this->disableCoreUpdates)
                    continue;
            }
            else {
                if ($this->isMarkedAsIgnored($update['code'])) {
                    $ignoredItems[] = $update;
                    continue;
                }
            }

            $items[] = $update;
        }

        $result['count'] = $updateCount;
        $result['items'] = $items;
        $result['ignoredItems'] = $ignoredItems;

        return $result;
    }

    public function getInstalledItems($type = null)
    {
        if ($this->installedItems)
            return ($type && isset($this->installedItems[$type]))
                ? $this->installedItems[$type] : $this->installedItems;

        $installedItems = [];

        $extensionVersions = Extension::pluck('version', 'name');
        foreach ($extensionVersions as $code => $version) {
            $installedItems['extensions'][] = [
                'name' => $code,
                'ver' => $version,
                'type' => 'extension',
            ];
        }

        $themeVersions = Theme::pluck('version', 'code');
        foreach ($themeVersions as $code => $version) {
            $installedItems['themes'][] = [
                'name' => $code,
                'ver' => $version,
                'type' => 'theme',
            ];
        }

        if (!is_null($type))
            return $installedItems[$type] ?? [];

        return $this->installedItems = array_collapse($installedItems);
    }

    public function requestApplyItems($names)
    {
        $applies = $this->getHubManager()->applyItems($names);

        if (isset($applies['data'])) foreach ($applies['data'] as $index => $item) {
            $filterCore = array_get($item, 'type') == 'core' && $this->disableCoreUpdates;
            if ($filterCore || $this->isMarkedAsIgnored($item['code']))
                unset($applies['data'][$index]);
        }

        return $applies;
    }

    public function ignoreUpdates($names)
    {
        $ignoredUpdates = $this->getIgnoredUpdates();

        foreach ($names as $item) {
            if (array_get($item, 'action', 'ignore') == 'remove') {
                unset($ignoredUpdates[$item['name']]);
                continue;
            }

            $ignoredUpdates[$item['name']] = true;
        }

        setting()->set('ignored_updates', $ignoredUpdates);

        return true;
    }

    public function getIgnoredUpdates()
    {
        return array_dot(setting()->get('ignored_updates') ?? []);
    }

    public function isMarkedAsIgnored($code)
    {
        if (!collect($this->getInstalledItems())->firstWhere('name', $code))
            return false;

        return array_get($this->getIgnoredUpdates(), $code, false);
    }

    public function setSecurityKey($key, $info)
    {
        params()->set('carte_key', $key ?: '');

        if ($info && is_array($info))
            params()->set('carte_info', $info);

        params()->save();

        resolve(ComposerManager::class)->addAuthCredentials(null, array_get($info, 'email', ''), $key);
    }

    //
    //
    //

    /**
     * @deprecated Use composer instead, remove in v5
     */
    public function downloadFile($fileCode, $fileHash, $params = [])
    {
        $filePath = $this->getFilePath($fileCode);

        if (!is_dir($fileDir = dirname($filePath)))
            mkdir($fileDir, 0777, true);

        return $this->getHubManager()->downloadFile($filePath, $fileHash, $params);
    }

    /**
     * @deprecated Use composer instead, remove in v5
     */
    public function extractCore($fileCode)
    {
        ini_set('max_execution_time', 3600);

        $configDir = base_path('/config');
        $configBackup = base_path('/config-backup');
        File::moveDirectory($configDir, $configBackup);

        $result = $this->extractFile($fileCode);

        File::copyDirectory($configBackup, $configDir);
        File::deleteDirectory($configBackup);

        return $result;
    }

    /**
     * @deprecated Use composer instead, remove in v5
     */
    public function extractFile($fileCode, $extractTo = null)
    {
        $filePath = $this->getFilePath($fileCode);
        if ($extractTo)
            $extractTo .= '/'.str_replace('.', '/', $fileCode);

        if (is_null($extractTo))
            $extractTo = base_path();

        if (!file_exists($extractTo))
            mkdir($extractTo, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $zip->extractTo($extractTo);
            $zip->close();
            @unlink($filePath);

            return true;
        }

        throw new ApplicationException('Failed to extract '.$fileCode.' archive file');
    }

    /**
     * @deprecated Use composer instead, remove in v5
     */
    public function getFilePath($fileCode)
    {
        $fileName = md5($fileCode).'.zip';

        return storage_path("temp/{$fileName}");
    }

    /**
     * @return \Igniter\System\Classes\HubManager
     */
    protected function getHubManager()
    {
        return $this->hubManager;
    }

    protected function parseTagDescription($update)
    {
        $tags = collect(array_get($update, 'tags.data', []))
            ->map(function ($tag) {
                if (strlen($tag['description']))
                    $tag['description'] = Markdown::parse($tag['description'])->toHtml();

                return $tag;
            })
            ->all();

        array_set($update, 'tags.data', $tags);

        return $update;
    }
}
