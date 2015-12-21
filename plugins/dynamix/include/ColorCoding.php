<?PHP
/* Copyright 2015, Lime Technology
 * Copyright 2015, Bergware International.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
// Color coding for syslog and disk log
$match =
[['class' => 'text',
  'text'  => ['to the standard error','non[ -]fatal error','correct gpt errors']
 ],
 ['class' => 'warn',
  'text'  => ['acpi error','preclear_disk','acpi warning','acpi exception','spurious','hpa','host protected area','invalid signature','(soft|hard) resetting ',' failed[ ,]','\<errno=[^0]','limiting speed to',': replayed','duplicate (object|error)',' checksum','warning','conflicts','kill','power is back','gpt:partition_entry','no floppy controller','accepted password']
 ],
 ['class' => 'error',
  'text'  => ['error','emask ','parity incorrect','fsck\?','invalid opcode:','nobody cared','unknown boot option',' ata[0-9\. ]+: disabled','dma disabled','kernel bug ','write protect is on','call trace','tainted','kernel:  \[','out[ _]of[ _]memory','killed','hpa detected: current [0-9]*055,','power failure']
 ],
 ['class' => 'system',
  'text'  => ['checksumming','get value of subfeature','mhz processor','cpu: intel','cpu[0-9]: intel','cpu: amd','cpu[0-9]: amd','kernel: processors:','kernel: memory:','kernel: smp:','b highmem',' lowmem ',' md: xor using','bogomips','kernel: console: ',' thermal zone',' adding [0-9]+k swap on ','kernel command line:','_sse','found.*chip','controller',' version ','mouse|speaker|kbd port|aux port|ps\/2|keyboard','driver','throttling rate']
 ],
 ['class' => 'array',
  'text'  => [': unraid system','key detected, registered',': unregistered',' mdcmd ',' md: ','super.dat ',': running, size:']
 ]
];
?>