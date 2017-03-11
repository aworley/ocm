//  11-20-2013 - caw - Created script to calculate yearly benefits protected or obtained for new tab LSAC

document.ws.lsac_protect_fed_benefit.focus();

function annual_benefit_protected_change()
{	
	if(isNumeric(document.ws.lsac_protect_mth_benefits.value))
	{
		num = (document.ws.lsac_protect_mth_benefits.value * 12);
		document.ws.lsac_protect_annual_benefits.value = num.toFixed(2);
	}
	else
	{
		num = 0;
		document.ws.lsac_protect_annual_benefits.value = num.toFixed(2);		
	}
}

function annual_benefit_obtained_change()
{		
	if (isNumeric(document.ws.lsac_obtain_mth_benefits.value))
	{
		num = (document.ws.lsac_obtain_mth_benefits.value * 12);
		document.ws.lsac_obtain_annual_benefits.value = num.toFixed(2);
	}
	else
	{
		num = 0;
		document.ws.lsac_obtain_annual_benefits.value = num.toFixed(2);
	}
}

function isNumeric(possibleNumber) 
{
	if(possibleNumber == null || possibleNumber.length == 0)
	{
		return false;
	}
	var validChars = '0123456789.';
	for(var i = 0; i < possibleNumber.length; i++) 
	{
		if(validChars.indexOf(possibleNumber.charAt(i)) == -1)
		{
			return false;
		}
	}
	return true;
}
