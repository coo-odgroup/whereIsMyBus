<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ODBUS Email</title>
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
                    <div style="color:#323232;letter-spacing:0.28px;font-size:24px;line-height:30px;margin-top:4px;font-weight:600">
                      Your bus booking is confirmed
                    </div>
                   
                  <div style="padding-top:16px">
                    <div style="border-radius:12px;background-color:#fff">
                      <div style="background-color:#0f204b;border-top-left-radius:12px;border-top-right-radius:12px;font-size:12px;line-height:16px;color:#f7f7f7;padding-top:12px;padding-bottom:12px">
                      <table style="margin-left:12px">
                        <tbody><tr>
                          <td>
                            <img align="center" src="{{url('public/images/bus.png')}}" alt="ODBUS" title="ODBUS" style="width:32px;height:32px">
                          </td>
                          <td style="padding-right:8px;padding-left:8px; text-align: left;">
                            <p style="font-size:14px;line-height:24px;font-weight:600;margin:0;color:#fff">
                              {{$busname}}-{{$busNumber}}
                            </p>
                            <p style="font-weight:500;font-size:12px;line-height:16px;color:#fff;margin:0;opacity:0.6">
                              {{$bus_sitting}},{{$bus_type}}
                            </p>
                          </td>
                        </tr>
                      </tbody></table>
                    </div>
                    <div style="padding:30px 16px;border-bottom-style:solid;border-bottom-width:1px;border-left-style:solid;border-left-width:1px;border-right-style:solid;border-right-width:1px;border-color:#f7f7f7;border-bottom-left-radius:12px;border-bottom-right-radius:12px">
                      <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tbody><tr>
                          <td style="text-align: left;">
                            <div style="text-overflow:ellipsis;overflow:hidden;white-space:nowrap;font-weight:600;font-size:18px;line-height:18px;">
                               {{$start}}
                            </div>
                          </td>
                          <td >
                            <div style="text-overflow:ellipsis;overflow:hidden;white-space:nowrap;font-weight:600;font-size:18px;line-height:18px;text-align:end">
                               {{$end}}
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                      <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tbody><tr>
                          <td style="text-align: left;">
                            <div style="font-size:16px;line-height:24px;font-weight:500; color:gray;">
                              {{$departureTime}}
                            </div>
                          </td>
                          <td>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tbody><tr width="100%">
                                <td>
                                  <div style="width:100%;margin-top:4px;margin-bottom:4px;border-width:1px;border-style:dotted;border-color:yellow;display:block;border-bottom-style:solid;border-bottom-width:1px;border-color:#e6e6e6" text=""></div>
                                </td>
                                <td style="padding:4px 12px;background-color:#f7f7f7;border-radius:4px;text-align:center;font-size:12px;line-height:16px;font-weight:500;color:#666;margin-right:4px;margin-left:4px">
                                  <span><img align="center" src="{{url('public/images/arrow-pointing.png')}}" style="width:24px;height:24px"></span>
                                </td>
                                <td>
                                  <div style="margin-top:4px;margin-bottom:4px;border-width:1px;border-width:1px;border-style:dotted;display:block;border-bottom-style:solid;border-bottom-width:1px;border-color:#e6e6e6" text=""></div>
                                </td>
                              </tr>
                            </tbody></table>
                          </td>
                          <td>
                            <div style="font-size:16px;line-height:24px;font-weight:500;text-align:end; color:gray;">
                              {{$arrivalTime}}
                            </div>
                          </td>
                        </tr>
                      </tbody></table>
                      
                        <div>
                          <div>
                            <div style="border-width:1px;border-bottom-style:solid;border-bottom-width:1px;border-style:dotted;border-color:yellow;margin-top:16px;margin-bottom:16px;display:block;border-color:#e6e6e6" text=""></div>
                          </div>
                        </div>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tbody><tr>
                            <td style="background:#fff">
                              <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody><tr>
                                  <td style="text-align:left;">
                                    <div>
                                      <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tbody><tr>
                                          <td width="24px">
                                            <img align="center" src="{{url('public/images/radio.png')}}"  style="width:24px;height:24px" >
                                          </td>
                                          <td>
                                            <div style="margin-left:12px;font-weight:600;font-size:14px;line-height:24px">
                                              Boarding point
                                            </div>
                                          </td>
                                          <td align="end" style="text-align:end;display:none">
                                            
                                          </td>
                                        </tr>
                                      </tbody></table>
                                    </div>
                                    <div style="padding-left:23px;border-left:1px dotted #e9e9e9;margin-left:12px;padding-bottom:12px;padding-right:12px;color:#666666;font-weight:500">
                                      {{$boarding_point}} , {{$conductor_number}}
                                    </div>
                                  </td>
                                </tr>
                                <tr>
                                  <td style="text-align:left;">
                                    <div style="padding-top:3px">
                                      <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tbody><tr>
                                          <td width="24px">
                                            <img align="center" src="{{url('public/images/map.png')}}"  style="width:24px;height:24px">
                                          </td>
                                          <td>
                                            <div style="margin-left:12px;font-weight:600;font-size:14px;line-height:24px">
                                              Drop-off point
                                            </div>
                                          </td>
                                          <td align="end" style="text-align:end;display:none">
                                            
                                          </td>
                                        </tr>
                                      </tbody></table>
                                      <div style="padding-left:36px;padding-right:12px;color:#666666;font-weight:500">
                                        {{$dropping_point}}
                                      </div>
                                    </div>
                                  </td>
                                </tr>
                              </tbody></table>
                            </td>
                          </tr>
                        </tbody></table>
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
                       
                        <table style="width:100%;margin-top:32px" border="0" cellspacing="0" cellpadding="0" align="center">
                          <tbody><tr>
                            <td style="font-size:14px;line-height:24px;font-weight:500;color:#1a1a1a">
                              <div style="line-height:20px">
                                <table style="line-height:1.5;table-layout:fixed;border-collapse:collapse">
                                  <tbody>
                                    <tr>
                                      <td style="padding-right:0.5em;padding-left:0.2em;vertical-align:text-top;font-size:16px"><span style="color:#ff0000;">*</span></td>
                                      <td>Your ticket details are attached with this e-mail.</td>
                                    </tr>
                                    <tr>
                                      <td style="padding-right:0.5em;padding-left:0.2em;vertical-align:text-top;font-size:16px"><span style="color:#ff0000;">*</span></td>
                                      <td>
                                        Service Fees, Discounts (if any) are non-refundable.
                                      </td>
                                    </tr>
                                    <tr>
                                      <td style="padding-right:0.5em;padding-left:0.2em;vertical-align:text-top;font-size:16px"><span style="color:#ff0000;">*</span></td>
                                      <td>
                                        Cancellation charges to be calculated on base fare (for details please go through cancellation policy)
                                      </td>
                                    </tr>
                                   
                                  </tbody>
                                </table>
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