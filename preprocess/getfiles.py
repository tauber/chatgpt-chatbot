import os
import pandas as pd
from IPython.display import display
import re

class DirFiles:
    def __init__(self, dir):
        # assign directory
        self.directory = dir
        self.df = pd.DataFrame(columns=["path", "text"])

    # type is the type (extension) of files to generate a list of.
    def GenFileList(self, type):
        self.path = []
        for root, dirs, files in os.walk(self.directory):
            for filename in files:
                fname, fext =  os.path.splitext(filename)
                if fext == type:
                    self.path.append(os.path.join(root, filename))

    # Create a dataframe with the text in the files.
    def ParseFileList(self):
        inForm = False
        df_loc = -1
        for path in self.path:
            # empty lines indicate the start of a new text chunk.
            chunkText = ""
            file = open(path, "r")
            for lines in file:
                # skip all lines inside a form
                if inForm:
                    if lines == "Bottom of Form\n":
                        inForm = False
                
                elif lines == "Top of Form\n":
                    inForm = True
                    chunkText = ""

                # empty line means a new document chunk.
                elif lines == "\n":
                    chunkText = ""
                          
                # ignore short lines with words all in caps = divider titles => empty line.
                elif len(lines) < 50 and lines == lines.upper():
                    chunkText = ""

                # ignore lines with separators = menu items => empty line.
                elif lines.find("|") >= 0:
                    chunkText = ""

                # ignore lines that say they are the menu => empty line.
                elif lines.lower() == "menu\n":
                    chunkText = ""

                # remove copyright.
                elif lines.find("(c)") >= 0 or lines.find("(r)") >= 0:
                    chunkText = ""

                else:
                    # if we have text after one or more empty line it is a new document chunk
                    if chunkText == "":
                        df_loc += 1
                        chunkText = lines

                    # if the chunkText was a question and answer and the new line is another question, it is a new document chunk
                    elif lines.find('?')>0 and chunkText.find('?')>0:
                        df_loc += 1
                        chunkText = lines
                    else:
                        chunkText += lines

                    self.df.loc[df_loc] = [path, chunkText]

    # Do data cleanup: 
    # maxLinkLen = maximum length of the text for a title or a link, anything longer is considered
    #              to regular text, poorly formatted. 
    def CleanText(self, maxLinkLen = 150):
        nLenOrig = self.df.count()

        # remove duplicates.
        self.df.drop_duplicates("text", inplace=True)

        # convert bulleted lists to commas 
        def convertBullets(b):
            # first \n* occurance follows a list header.
            listHeader = re.compile(r'(^[\w ]+)(\n\*)', flags=re.MULTILINE)
            c = listHeader.sub(r'\1:', b)
            c = c.replace("\n*", ",")
            c = c.replace("*", "")
            c = c.replace("=", "")

            return c

        self.df["text"] = self.df["text"].apply(convertBullets)

        # remove all short paragraphs, they add no useful information
        self.df = self.df[self.df["text"].apply(len) > maxLinkLen]

        def removeTitlesAndLinks(t):
            isTorL = True      # toggle indicating whether the text contains only titles or links

            lines = t.split("\n")

            for line in lines:
                # chunkText with short lines without punctuation at the end is treated as title or link and should be removed.
                if isTorL and (len(line) > maxLinkLen or any(punctuation in line[-2:] for punctuation in [".", ",", ":", "?", ";", "!"])):
                    isTorL = False

            return not isTorL

        nLenOrig = self.df.count()

        self.df = self.df[self.df["text"].apply(removeTitlesAndLinks)]

        # return how many rows were removed
        return nLenOrig.text, self.df.count().text, nLenOrig.text - self.df.count().text

    def Save(self, csv_file = "full_site_dataframe.csv"):
        self.df.to_csv(self.directory + csv_file)
