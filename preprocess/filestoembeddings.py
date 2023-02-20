from getfiles import DirFiles

dir = '/httrack-idlweb/www.example.com/'

files = DirFiles(dir)
files.GenFileList('.txt')
files.ParseFileList()
print(files.CleanText())
files.Save()
