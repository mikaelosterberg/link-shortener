<x-filament-panels::page>
    <div class="space-y-6">
        <!-- CSV Template Download Section -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">CSV Template</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Download the CSV template to ensure your data is formatted correctly.
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <x-filament::button
                        wire:click="downloadTemplate"
                        icon="heroicon-o-arrow-down-tray"
                        color="gray"
                        wire:loading.attr="disabled"
                        wire:target="downloadTemplate"
                    >
                        <span wire:loading.remove wire:target="downloadTemplate">Download Template</span>
                        <span wire:loading wire:target="downloadTemplate">Downloading...</span>
                    </x-filament::button>
                </div>
            </div>
            
            <!-- CSV Format Information -->
            <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">CSV Format Requirements:</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <strong>Required Column:</strong>
                        <ul class="mt-1 space-y-1 ml-4">
                            <li>• <code>original_url</code> - The destination URL (must include http:// or https://)</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Basic Optional Columns:</strong>
                        <ul class="mt-1 space-y-1 ml-4">
                            <li>• <code>custom_slug</code> - Custom short code</li>
                            <li>• <code>group_name</code> - Link group (creates if doesn't exist)</li>
                            <li>• <code>expires_at</code> - Expiration date (YYYY-MM-DD format)</li>
                            <li>• <code>password</code> - Password protection</li>
                        </ul>
                    </div>
                    <div>
                        <strong>Advanced Optional Columns:</strong>
                        <ul class="mt-1 space-y-1 ml-4">
                            <li>• <code>click_limit</code> - Maximum clicks (number)</li>
                            <li>• <code>redirect_type</code> - 301, 302, 307, or 308 (defaults to 302)</li>
                            <li>• <code>is_active</code> - 1 for active, 0 for inactive (defaults to 1)</li>
                            <li>• <code>notes</code> - Additional notes or description</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Tip:</strong> Most fields can be left empty for sensible defaults: links default to active (is_active=1), redirect_type defaults to 302, and links without groups go to your default group.
                        Groups will be created automatically if they don't exist. Custom slugs must be unique and contain only letters, numbers, hyphens, and underscores.
                    </p>
                </div>
                
                <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-md">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        <strong>Important:</strong> Don't upload the template file directly! Download it, replace the example data with your own links, then upload your edited file.
                    </p>
                </div>
                
                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Auto-Fix:</strong> Invalid redirect_type values will automatically default to 302. 
                        Valid options are 301, 302, 307, or 308. Any other value (like 1, 2, 3, etc.) will become 302.
                    </p>
                </div>
            </div>
        </div>

        <!-- Import Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <form wire:submit="importCsv">
                {{ $this->form }}
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        icon="heroicon-o-arrow-up-tray"
                        wire:loading.attr="disabled"
                        wire:target="importCsv"
                    >
                        <span wire:loading.remove wire:target="importCsv">Import CSV</span>
                        <span wire:loading wire:target="importCsv">Processing...</span>
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Import Guidelines -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Important Notes
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <ul class="space-y-1">
                            <li>• Maximum file size is 10MB</li>
                            <li>• All URLs must be valid and include http:// or https://</li>
                            <li>• Custom slugs must be unique across all existing links</li>
                            <li>• Date formats should be YYYY-MM-DD or YYYY-MM-DD HH:MM:SS</li>
                            <li>• Import will stop if any validation errors are found</li>
                            <li>• Large imports may take a few moments to process</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>