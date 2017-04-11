unit uMain;

{
  author: ZuBy

  https://github.com/rzaripov1990/PUSHTestFCM

  2017
}

interface

uses
  System.SysUtils, System.Types, System.UITypes, System.Classes, System.Variants,
  FMX.Types, FMX.Controls, FMX.Forms, FMX.Graphics, FMX.Dialogs, FMX.Controls.Presentation,
  FMX.StdCtrls, FMX.Objects, System.Actions, FMX.ActnList, FMX.StdActns, FMX.MediaLibrary.Actions,

  System.PushNotification
{$IFDEF ANDROID}, FMX.PushNotification.Android{$ENDIF}
{$IFDEF IOS}, FMX.PushNotification.IOS{$ENDIF};

type
  TFormMain = class(TForm)
    rHeader: TRectangle;
    lbTitle: TLabel;
    Text1: TText;
    tToken: TText;
    sbShare: TSpeedButton;
    ActionList1: TActionList;
    ShowShareSheetAction1: TShowShareSheetAction;
    SpeedButton1: TSpeedButton;
    procedure sbShareClick(Sender: TObject);
    procedure FormCreate(Sender: TObject);
    procedure SpeedButton1Click(Sender: TObject);
  private
    FDeviceID: string;
    FDeviceToken: string;

    FPushService: TPushService;
    FPushServiceConnection: TPushServiceConnection;

    procedure OnReceiveNotificationEvent(Sender: TObject; const ANotification: TPushServiceNotification);
    procedure OnServiceConnectionChange(Sender: TObject; AChange: TPushService.TChanges);

    { Private declarations }
    procedure PushServiceRegister;

    procedure RegisterDevice;
  public
    { Public declarations }
  end;

var
  FormMain: TFormMain;

implementation

{$R *.fmx}

uses
  System.Threading, System.Net.HTTPClient;

const
  FAndroidServerKey = '63538920422';

procedure TFormMain.FormCreate(Sender: TObject);
begin
  PushServiceRegister;
end;

procedure TFormMain.OnReceiveNotificationEvent(Sender: TObject; const ANotification: TPushServiceNotification);
var
  aText: string;
begin
  // это событие срабатывает при открытом приложении
  aText := ANotification.DataObject.GetValue( {$IFDEF ANDROID} 'message' {$ELSE} 'alert' {$ENDIF}).Value;
  ShowMessage(aText);
end;

procedure TFormMain.OnServiceConnectionChange(Sender: TObject; AChange: TPushService.TChanges);
begin
  if (TPushService.TChange.DeviceToken in AChange) and Assigned(FPushServiceConnection) then
  begin
    FDeviceID := FPushService.DeviceIDValue[TPushService.TDeviceIDNames.DeviceID];
    FDeviceToken := FPushService.DeviceTokenValue[TPushService.TDeviceTokenNames.DeviceToken];

    // тут отправляем в хранилище токенов (на сервер с БД например)
    RegisterDevice;

    tToken.Text := FDeviceToken;
  end;
end;

procedure TFormMain.PushServiceRegister;
begin
  FPushService := nil;
  FPushServiceConnection := nil;

{$IF defined(ANDROID)}
  FPushService := TPushServiceManager.Instance.GetServiceByName(TPushService.TServiceNames.GCM);
  FPushService.AppProps[TPushService.TAppPropNames.GCMAppID] := FAndroidServerKey;
{$ENDIF}
{$IF defined(IOS) AND defined(CPUARM)}
  FPushService := TPushServiceManager.Instance.GetServiceByName(TPushService.TServiceNames.APS);
{$ENDIF}
  if Assigned(FPushService) then
  begin
    FPushServiceConnection := TPushServiceConnection.Create(FPushService);
    FPushServiceConnection.OnChange := OnServiceConnectionChange;
    FPushServiceConnection.OnReceiveNotification := OnReceiveNotificationEvent;
    FPushServiceConnection.Active := true;

    FDeviceID := FPushService.DeviceIDValue[TPushService.TDeviceIDNames.DeviceID];
    FDeviceToken := FPushService.DeviceTokenValue[TPushService.TDeviceTokenNames.DeviceToken];

    // тут отправляем в хранилище токенов (на сервер с БД например)
    RegisterDevice;

    tToken.Text := FDeviceToken;
  end;
end;

procedure TFormMain.RegisterDevice;
begin
  TTask.Run(
    procedure
    var
      aHTTP: THTTPClient;
    begin
      aHTTP := THTTPClient.Create;
      try
        aHTTP.Get('http://rzaripov.kz/pushTest/api.php?method=saveToken&deviceID=' + FDeviceID + '&deviceToken=' +
          FDeviceToken + '&platform='{$IFDEF ANDROID} + 'ANDROID' {$ELSEIF defined(IOS)} + 'IOS' {$ENDIF});
      finally
        FreeAndNil(aHTTP);
      end;
    end);
end;

procedure TFormMain.sbShareClick(Sender: TObject);
begin
  ShowShareSheetAction1.TextMessage := FDeviceToken;
  ShowShareSheetAction1.ExecuteTarget(sbShare);
end;

procedure TFormMain.SpeedButton1Click(Sender: TObject);
begin
  tToken.Text := FDeviceToken;
end;

end.
