<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GST Invoice</title>
<style type="text/css">
body{
  font: small / 1.5 Arial, Helvetica, sans-serif;
  padding: 0;
  margin: 0;
}
p{
  padding: 0;
  margin: 0;
}
</style>
</head>
<body>

<style type="text/css">
  body{
        font: small / 1.5 Arial, Helvetica, sans-serif;
  }
</style>
<center style="width:100%;table-layout:fixed;background:#fff">
      <div style="max-width:600px;margin:0 auto;padding:0">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tbody><tr>
                          <td style="padding-left:12px;">
                            <div style="text-align:left; background-color: #fff;">
                <img align="center" src="{{url('public/template/logo.png')}}" alt="ODBUS" title="ODBUS" style="margin-bottom:28px;margin-top:12px;width:160px;">
              </div>
                          </td>
                          <td style="padding-right:12px;">
                            <div style="text-overflow:ellipsis;overflow:hidden;white-space:nowrap;color:#1a1a1a;font-weight:500;font-size:14px;line-height:20px;text-align:end">
                             <h3 style="margin:0px;">e-Ticketing</h3>
                              <p style="margin:0px;"><strong>PNR</strong> - {{$pnr}}<br/>
                              <strong>Journey Date</strong> - {{date('j \\ F Y', strtotime($journeydate))}}</p>
                            </div>
                          </td>
                        </tr>
                      </tbody></table>
        <table style="padding:0px 6px" width="100%" border="0" cellspacing="0" cellpadding="0" align="center">
          <tbody><tr>
           
            <td style="background-color:#f4f5ff;width:100%;height:100%; border-radius:12px;border: 1px solid #dadcee;">
              <div style="border-radius:12px">
                <div style="padding:16px;border-radius:12px">
                  <div style="text-align:center">
                    <img width="40" height="40" align="center" src="{{url('public/images/checked.png')}}" alt="tick" style="height:40px;width:40px;display:inline-block;margin-top:0px;margin-bottom:0;text-align:center" >
                  
                  <div style="padding-top:16px">
                    <div style="border-radius:12px;background-color:#fff">
                     
                    <div style="padding:30px 16px;border-bottom-style:solid;border-bottom-width:1px;border-left-style:solid;border-left-width:1px;border-right-style:solid;border-right-width:1px;border-color:#f7f7f7;border-bottom-left-radius:12px;border-bottom-right-radius:12px">                     

                        
                        <table style="width:100%;margin-top:36px" border="0" cellspacing="0" cellpadding="0" align="center">
                          <tbody><tr>
                            <td style="font-size:20px;line-height:28px;font-weight:600;color:#1a1a1a">Need help?</td>
                          </tr>
                          <tr>
                            <td>
                              <div style="font-size:14px;line-height:24px;font-weight:500;color:#1a1a1a;margin-top:12px">
                                For any queries or assistance contact us at
                                <a style="color:#3366cc;margin-top:12px;font-size:14px;line-height:24px;font-weight:500" href="tel:+91%209583918888" rel="noreferrer" target="_blank">+91 9583918888</a>, <a style="color:#3366cc;margin-top:12px;font-size:14px;line-height:24px;font-weight:500" href="mailto:support@odbus.in">support@odbus.in</a>
                              </div>
                            </td>
                          </tr>
                        </tbody></table>
                      
                        <div>
                          <div style="border-width:1px;border-bottom-style:solid;border-bottom-width:1px;border-style:dotted;border-color:#e6e6e6;margin-top:16px;margin-bottom:16px;display:block" text=""></div>
                        </div>
                        <table style="width:100%" border="0" cellspacing="0" cellpadding="0" align="center">
                          <tbody><tr>
                            <td>
                              <div style="line-height:20px">
                                This email was sent from a notification-only address
                                that cannot accept incoming email. Please do not
                                reply to this message.
                              </div>
                            </td>
                          </tr>
                        </tbody></table>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody></table>
            <div style="line-height:10px;padding:0 0 20px 0;margin-top:20px;text-align:center;font-size:12px;color:#999; margin-bottom: 20px;">
              <div style="color:#666666;font-weight:500">
                Copyright @ ODBUS Tours & Travels PVT. LTD. All Rights Reserved
              </div>
            </div>
          </div>
        </center>