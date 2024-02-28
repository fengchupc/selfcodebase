# Enter-PSSession -ComputerName 192.168.15.45 -Credential MEL\rfeng

winrs -r:http://192.168.15.45:5985 -u:MEL\rfeng -p:Tum96820 -d:C:\Users\rfeng\Desktop\cmd "powershell"

