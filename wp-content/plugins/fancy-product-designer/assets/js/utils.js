window.fpdDownloadPrintFile = (fancyProductDesigner, callback) => {
	if (fpd_setup_configs.misc.pro_export_enabled && fancyProductDesigner) {
		fancyProductDesigner.toggleSpinner(true);

		let printData = fancyProductDesigner.getPrintOrderData(fpd_setup_configs.misc.export_method == "svg2pdf");

		printData.name = fancyProductDesigner?.viewInstances[0]?.viewData?.productTitle;

		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get("order") && urlParams.get("item_id"))
			printData.name = urlParams.get("order") + "_" + urlParams.get("item_id");

		if (fpd_setup_configs.misc.export_method.includes("nodecanvas")) {
			printData.product_data = fancyProductDesigner.getProduct();
		}

		const data = {
			action: "fpd_pr_export",
			print_data: JSON.stringify(printData),
		};

		jQuery.post(
			fpd_setup_configs.admin_ajax_url,
			data,
			function (response) {
				if (response && response.file_url) {
					window.open(response.file_url, "_blank");
				} else {
					alert("Something went wrong. Please contact the site owner!");
				}

				fancyProductDesigner.toggleSpinner(false);

				callback && callback();
			},
			"json"
		);
	}
};
