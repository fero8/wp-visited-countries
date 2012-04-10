function wpvc_ammap( s ) {
	s.path = s.path + 'ammap/';
	var so = new SWFObject(s.path + "ammap.swf", "ammap", s.width, s.height, "8", s.bgcolor);
	so.addVariable("path", s.path);
	so.addVariable("data_file", escape(s.path + "ammap_data_" + s.blogid + ".xml"));
	so.addVariable("settings_file", escape(s.path + "ammap_settings_" + s.blogid + ".xml"));
	so.addVariable("preloader_color", "#999999");
	so.write(s.id);
}