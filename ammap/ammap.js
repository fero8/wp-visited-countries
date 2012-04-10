function wpvc_ammap(path, width, height, bgcolor, id) {
	path = path + 'ammap/';
	var so = new SWFObject(path + "ammap.swf", "ammap", width, height, "8", bgcolor);
	so.addVariable("path", path);
	so.addVariable("data_file", escape(path + "ammap_data.xml"));
	so.addVariable("settings_file", escape(path + "ammap_settings.xml"));
	so.addVariable("preloader_color", "#999999");
	so.write(id);
}