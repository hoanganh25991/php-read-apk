#PHP read info from apk file

##aapt on linux (ubuntu)

PHP apk parser CAN NOT understand hashed path for application-icon,..

	<application theme="0x7f080035" label="0x7f070042" 
	icon="0x7f020038" name="us.originally.garlock.controllers.Gar
	lockApplication" allowBackup="0xffffffff" hardwareAccelerated="
	0xffffffff" largeHeap="0xffffffff">

Using `aapt` read out: Where the icon locate?

	/*#update source list to install `aapt`
	sudo nano /etc/apt/sources.list

	#append this line
	deb http://us.archive.ubuntu.com/ubuntu vivid main universe

	sudo apt-get install aapt

	#in folder contain "[file.apk]", run
	aapt d badging [file.apk]

	#result
	application-icon-213:'res/drawable-xhdpi-v4/app_icon.png'
	application-icon-240:'res/drawable-xhdpi-v4/app_icon.png'
	

	#appt document http://elinux.org/Android_aapt



##7zip
Base on icon-path, unzip to get it out from [file.apk]

	/*#instal 7zip
	apt-get install p7zip-full

	#run command
	7z e [archive.zip] -o[outputdir] [fileFilter1] [fileFilter2] -r

	#in [outputdir] get file base on [fileFilter1] [fileFilter2]
	#only extract what we need



	