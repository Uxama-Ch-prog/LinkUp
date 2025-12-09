<?php
function getDirectoryStructure($dir, $prefix = '') {
    $structure = '';
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        
        $path = $dir . '/' . $item;
        $structure .= $prefix . $item . "\n";
        
        if (is_dir($path) && 
            !in_array($item, ['node_modules', 'vendor', 'storage', '.git', '.vscode'])) {
            $structure .= getDirectoryStructure($path, $prefix . '  ');
        }
    }
    
    return $structure;
}

$structure = getDirectoryStructure(__DIR__);
file_put_contents('project_structure.txt', $structure);
echo "Structure saved to project_structure.txt\n";