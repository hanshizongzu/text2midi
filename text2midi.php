<?php
// addNote(channel, pitch, duration[, time[, velocity]])

// channel
// 音轨，都应是0。

// pitch
// 音调，音高，可以 0-127 取值，由低到高。

// duration
// 持续的时间，这个音调的持续的时间，32是最大化的最小一格。

// time
// 留空的时间，这个音调前留空的时间，32是最大化的最小一格。

// velocity
// 力度
// 实测：这个是力度，不是速度。

// 缺点
// 不能和弦，也无需和弦，古音乐用不上。




// 读取目录
$dirRes = opendir(__DIR__);
while ($fileName = readdir($dirRes)) {
    if (preg_match('/\.MD$/', $fileName)) {
        break;
    }
}
closedir($dirRes);

if (!preg_match('/\.MD$/', $fileName)) {
    exit;
}

$file = realpath(__DIR__."/$fileName");

$qupu = file_get_contents($file);

// 中划线或下划线，前为注视后为乐谱
$qupu = str_replace('_', '-', $qupu);
$qupu = substr($qupu, strrpos($qupu, '-'));

$qupu = str_replace(["\n", "\r"], ' ', $qupu); // 去除换行
$qupu = preg_replace(["/\s+/"], ' ', $qupu); // 去除重复空格
$qupu = trim($qupu);

/*
格式说明：
50@32.64 40@72 40@72.32
50@64.64 40@72 40@72.32

其中 50@32.64 表示 `音调50 持续32 前空64` 只有前空可以省略
*/
$trackAdds = [];
foreach (explode(' ', $qupu) as $q) {
    if (preg_match('/(\d+)@(\d+)(\.\d+)?/', $q, $matches)) {
        if (!isset($matches[3])) {
            $trackAdds[] = "track.addNote(0, {$matches[1]}, {$matches[2]});";
        } else {
            $qianKong = substr($matches[3], 1);
            $trackAdds[] = "track.addNote(0, {$matches[1]}, {$matches[2]}, $qianKong);";
            unset($qianKong);
        }
    }
}

$tpl = <<<EOT
var fs = require('fs');
var Midi = require('jsmidgen');

var file = new Midi.File();
var track = new Midi.Track();
file.addTrack(track);

track.addNote(0, 127, 32, 64);

fs.writeFileSync('output.mid', file.toBytes(), 'binary');
EOT;

$content = str_replace('track.addNote(0, 127, 32, 64);', implode("\n", $trackAdds), $tpl);
$content = str_replace('output', $fileName, $content);

file_put_contents(__DIR__."/text2midi.js", $content);
