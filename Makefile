all:
	if [[ -e magento-ce-payment-plugin.zip ]]; then rm magento-ce-payment-plugin.zip; fi
	zip -r magento-ce-payment-plugin.zip app lib -x "*/test/*" -x "*/.git/*" -x "*/examples/*"
