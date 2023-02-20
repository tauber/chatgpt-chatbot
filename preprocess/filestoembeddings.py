from getfiles import DirFiles

dir = 'd:/users/zohar/jobs/idlweb/chatbot/httrack-idlweb/www.idlwebinc.com/'

files = DirFiles(dir)
files.GenFileList('.txt')
files.ParseFileList()
print(files.CleanText())
files.Save()
