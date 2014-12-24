**Virtual Machine Management**

Manage Virtual Machines using a libvirt-php module that provides PHP bindings for libvirt virtualization toolkit and therefore you can access libvirt directly from your PHP scripts with no need to have virt-manager or libvirt-based CLI/GUI tools installed. 
<a href="http://lime-technology.com/forum/index.php?topic=35858.0" title="2014.12.24
    Fixed domain.cfg missing error
    Fixed no disks domain auto start
2014.12.23
    Changed VNC to ip based for our OSX friends
    Added settings tab with default media and image settings
    Added debug checkbox 
2014.12.21-21a
    expanded usb devices to include bus and device 
    Add change of machine toggles VNC Mouse(usb tablet)
    change to q35 in dropdown  q35 equals pc-q35-2.1
    Reformat Create VM Page
2014.12.20-20a
    Added temp driver cdrom for windows installs.
         It will disapear after vm has shut down
    Added Machine type selection.  Usbtab doesn't work with q35
    Added usbtab selection under usb devices
    Fixed no domains sort error
    Removed Storage Pool tab and all storage pool functions.
    Added web based file trees to Create VM tab to access cdrom, 
         existing images and to create images.
    New vm images will be created based on name of vm in a 
         sub-folder of the same name similar to xenman plugin
    Added file tree for cdrom change for existing vm 
         ie. for switching to driver image for windows virtio drivers
    Removed Device tab
    condensed action messages
2014.12.03
    Updated styles sheet table spacing
2014.12.01
    Updated the plugin to dynamix compatible version for beta 12 and above
2014.11.29a
    added snapshot descriptions
    added disk dev name change
    changed method of saving xml so will save with snapshots
    removed dominfo and hostinfo page
    changed to KVM tab since webvirtmgr is dead
">Change Log</a>